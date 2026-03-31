import React, { useEffect, useState } from 'react';
import { Image, StyleSheet, Text, View } from 'react-native';
import * as ImagePicker from 'expo-image-picker';
import * as Location from 'expo-location';
import { apiRequest } from '../api';
import { Card, EmptyState, Field, LoadingState, Notice, Pill, PrimaryButton, Screen } from '../components/Ui';
import { palette, spacing, toneForStatus } from '../theme';

export function EvidenceScreen({ token, user }) {
  const [state, setState] = useState({ loading: true, error: '', shipments: [], settings: null });
  const [selectedShipment, setSelectedShipment] = useState(null);
  const [recipientName, setRecipientName] = useState('');
  const [signatureText, setSignatureText] = useState('');
  const [notes, setNotes] = useState('');
  const [photoDataUrl, setPhotoDataUrl] = useState('');
  const [photoPreview, setPhotoPreview] = useState('');
  const [coords, setCoords] = useState(null);
  const [submitting, setSubmitting] = useState(false);
  const [notice, setNotice] = useState({ type: 'info', message: '' });

  useEffect(() => {
    (async () => {
      try {
        const [shipments, settings] = await Promise.all([
          apiRequest('/shipments', { token }),
          apiRequest('/settings', { token }),
        ]);

        setState({ loading: false, error: '', shipments, settings });
      } catch (error) {
        setState({ loading: false, error: error.message, shipments: [], settings: null });
      }
    })();
  }, [token]);

  if (user.role !== 'driver') {
    return <Screen title="Prueba de entrega" subtitle="Registro de entrega en ruta."><EmptyState title="Modulo restringido" subtitle="Esta vista esta disponible para el perfil conductor." /></Screen>;
  }

  if (state.loading) {
    return <LoadingState label="Preparando captura de evidencia..." />;
  }

  if (state.error) {
    return <Screen title="Prueba de entrega" subtitle="Foto, firma y geolocalizacion de la entrega."><EmptyState title="No fue posible cargar tus envios visibles" subtitle={state.error} /></Screen>;
  }

  const readySteps = [selectedShipment, coords, photoPreview || photoDataUrl, signatureText.trim()].filter(Boolean).length;

  async function captureLocation() {
    const permission = await Location.requestForegroundPermissionsAsync();

    if (permission.status !== 'granted') {
      setNotice({ type: 'error', message: 'No se concedio acceso a la ubicacion.' });
      return;
    }

    const position = await Location.getCurrentPositionAsync({ accuracy: Location.Accuracy.Balanced });
    setCoords({ latitude: position.coords.latitude, longitude: position.coords.longitude });
    setNotice({ type: 'info', message: 'Ubicacion capturada correctamente.' });
  }

  async function capturePhoto() {
    const permission = await ImagePicker.requestCameraPermissionsAsync();

    if (permission.status !== 'granted') {
      setNotice({ type: 'error', message: 'No se concedio acceso a la camara.' });
      return;
    }

    const result = await ImagePicker.launchCameraAsync({
      allowsEditing: true,
      quality: 0.5,
      base64: true,
      mediaTypes: ['images'],
    });

    if (result.canceled || !result.assets?.length) {
      return;
    }

    const asset = result.assets[0];
    const mime = asset.mimeType || 'image/jpeg';
    setPhotoPreview(asset.uri);
    setPhotoDataUrl(`data:${mime};base64,${asset.base64}`);
    setNotice({ type: 'info', message: 'Foto cargada para la evidencia.' });
  }

  async function submitEvidence() {
    if (!selectedShipment) {
      setNotice({ type: 'error', message: 'Selecciona primero un envio visible.' });
      return;
    }

    if (!recipientName.trim()) {
      setNotice({ type: 'error', message: 'Indica el nombre del receptor.' });
      return;
    }

    if (state.settings?.requirePhoto && !photoDataUrl) {
      setNotice({ type: 'error', message: 'La configuracion actual exige foto.' });
      return;
    }

    if (state.settings?.requireSignature && !signatureText.trim()) {
      setNotice({ type: 'error', message: 'La configuracion actual exige firma textual.' });
      return;
    }

    setSubmitting(true);
    setNotice({ type: 'info', message: '' });

    try {
      const response = await apiRequest(`/shipments/${selectedShipment.id}/evidence`, {
        method: 'POST',
        token,
        data: {
          recipientName: recipientName.trim(),
          signatureText: signatureText.trim(),
          notes: notes.trim(),
          photoDataUrl: photoDataUrl || null,
          gpsLatitude: coords?.latitude ?? null,
          gpsLongitude: coords?.longitude ?? null,
          status: 'delivered',
          deliveryTimestamp: new Date().toISOString(),
        },
      });

      setNotice({ type: 'info', message: response.message || 'Evidencia registrada correctamente.' });
      setRecipientName('');
      setSignatureText('');
      setNotes('');
      setPhotoDataUrl('');
      setPhotoPreview('');
      setCoords(null);

      const shipments = await apiRequest('/shipments', { token });
      setState((current) => ({ ...current, shipments }));
      setSelectedShipment(null);
    } catch (error) {
      setNotice({ type: 'error', message: error.message || 'No fue posible registrar la evidencia.' });
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <Screen eyebrow="Ultima milla" title="Prueba de entrega" subtitle="Captura operativa con foto, firma textual y geolocalizacion para la ultima milla.">
      <Notice type={notice.type} message={notice.message} />

      <Card tone="dark">
        <Text style={styles.heroTitle}>Cierre de entrega</Text>
        <Text style={styles.heroCopy}>Registra la ultima milla con evidencia lista para auditoria y lectura operativa más clara.</Text>
        <View style={styles.requirementRow}>
          <Pill tone="dark">{state.shipments.length} envios visibles</Pill>
          <Pill tone={state.settings?.requirePhoto ? 'accent' : 'dark'}>Foto {state.settings?.requirePhoto ? 'obligatoria' : 'opcional'}</Pill>
          <Pill tone={state.settings?.requireSignature ? 'accent' : 'dark'}>Firma {state.settings?.requireSignature ? 'obligatoria' : 'opcional'}</Pill>
        </View>
      </Card>

      <Card>
        <View style={styles.sectionHeader}>
          <Text style={styles.sectionTitle}>Envios visibles</Text>
          <Pill tone={selectedShipment ? 'brand' : 'neutral'}>{selectedShipment ? '1 seleccionado' : 'Selecciona uno'}</Pill>
        </View>
        {state.shipments.length ? state.shipments.map((item) => {
          const active = selectedShipment?.id === item.id;

          return (
            <View key={item.id} style={[styles.shipmentRow, active && styles.shipmentRowActive]}>
              <View style={styles.shipmentInfo}>
                <Pill tone={toneForStatus(item.status)}>{item.status}</Pill>
                <Text style={styles.tracking}>{item.tracking}</Text>
                <Text style={styles.meta}>{item.destinationAddress || 'Sin direccion'}{item.destinationCity ? ` - ${item.destinationCity}` : ''}</Text>
              </View>
              <PrimaryButton label={active ? 'Seleccionado' : 'Usar'} onPress={() => setSelectedShipment(item)} tone={active ? 'brand' : 'outline'} style={styles.shipmentAction} />
            </View>
          );
        }) : <EmptyState title="Sin envios visibles" subtitle="Cuando tengas paquetes asignados apareceran aqui para registrar la entrega." />}
      </Card>

      {selectedShipment ? (
        <Card tone="soft">
          <Text style={styles.sectionTitle}>Envio seleccionado</Text>
          <Pill tone={toneForStatus(selectedShipment.status)}>{selectedShipment.status}</Pill>
          <Text style={styles.tracking}>{selectedShipment.tracking}</Text>
          <Text style={styles.meta}>{selectedShipment.destinationAddress || 'Sin direccion'}{selectedShipment.destinationCity ? ` - ${selectedShipment.destinationCity}` : ''}</Text>
        </Card>
      ) : null}

      <Card>
        <View style={styles.sectionHeader}>
          <Text style={styles.sectionTitle}>Captura de evidencia</Text>
          <Pill tone={readySteps >= 4 ? 'success' : 'accent'}>{readySteps}/4 listos</Pill>
        </View>
        <Field label="Receptor" value={recipientName} onChangeText={setRecipientName} placeholder="Nombre de quien recibe" autoCapitalize="words" />
        <Field label="Firma textual" value={signatureText} onChangeText={setSignatureText} placeholder="Nombre que quedara en la firma" autoCapitalize="words" />
        <Field label="Notas" value={notes} onChangeText={setNotes} placeholder="Observaciones de entrega" autoCapitalize="sentences" multiline />
        <View style={styles.actionRow}>
          <PrimaryButton label={coords ? 'Ubicacion lista' : 'Capturar ubicacion'} onPress={captureLocation} tone="outline" style={styles.actionButton} />
          <PrimaryButton label={photoPreview ? 'Repetir foto' : 'Tomar foto'} onPress={capturePhoto} tone="soft" style={styles.actionButton} />
        </View>
        {coords ? <Text style={styles.meta}>GPS: {coords.latitude.toFixed(5)}, {coords.longitude.toFixed(5)}</Text> : null}
        {photoPreview ? <Image source={{ uri: photoPreview }} style={styles.preview} /> : null}
        <PrimaryButton label={submitting ? 'Registrando...' : 'Registrar entrega'} onPress={submitEvidence} disabled={submitting} />
      </Card>
    </Screen>
  );
}

const styles = StyleSheet.create({
  heroTitle: {
    color: palette.textOnDark,
    fontSize: 24,
    fontWeight: '800',
  },
  heroCopy: {
    color: palette.textMutedOnDark,
    lineHeight: 20,
  },
  requirementRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
    marginTop: spacing.xs,
  },
  sectionHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: spacing.sm,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: '800',
    color: palette.text,
  },
  shipmentRow: {
    flexDirection: 'row',
    gap: spacing.md,
    alignItems: 'center',
    padding: spacing.sm,
    borderRadius: 18,
    backgroundColor: 'rgba(255, 255, 255, 0.62)',
    borderWidth: 1,
    borderColor: 'rgba(26, 38, 62, 0.06)',
  },
  shipmentRowActive: {
    borderColor: 'rgba(15, 123, 108, 0.18)',
    backgroundColor: 'rgba(15, 123, 108, 0.08)',
  },
  shipmentInfo: {
    flex: 1,
    gap: spacing.xs,
  },
  shipmentAction: {
    minWidth: 112,
  },
  actionRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
  },
  actionButton: {
    flex: 1,
    minWidth: 150,
  },
  tracking: {
    marginTop: 2,
    fontSize: 18,
    fontWeight: '800',
    color: palette.brandDeep,
  },
  meta: {
    color: palette.textMuted,
    lineHeight: 20,
  },
  preview: {
    width: '100%',
    height: 220,
    borderRadius: 18,
    backgroundColor: palette.surfaceMuted,
  },
});
