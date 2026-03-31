import React, { useEffect, useState } from 'react';
import { Image, StyleSheet, Text, View } from 'react-native';
import * as ImagePicker from 'expo-image-picker';
import * as Location from 'expo-location';
import { apiRequest } from '../api';
import { Card, EmptyState, Field, LoadingState, Notice, Pill, PrimaryButton, Screen } from '../components/Ui';
import { palette, spacing } from '../theme';

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
    return <Screen title="Prueba de entrega" subtitle="Registro de entrega en ruta."><EmptyState title="Módulo restringido" subtitle="Esta vista está disponible para el perfil conductor." /></Screen>;
  }

  if (state.loading) {
    return <LoadingState label="Preparando captura de evidencia..." />;
  }

  if (state.error) {
    return <Screen title="Prueba de entrega" subtitle="Foto, firma y geolocalización de la entrega."><EmptyState title="No fue posible cargar tus envíos visibles" subtitle={state.error} /></Screen>;
  }

  async function captureLocation() {
    const permission = await Location.requestForegroundPermissionsAsync();

    if (permission.status !== 'granted') {
      setNotice({ type: 'error', message: 'No se concedió acceso a la ubicación.' });
      return;
    }

    const position = await Location.getCurrentPositionAsync({ accuracy: Location.Accuracy.Balanced });
    setCoords({ latitude: position.coords.latitude, longitude: position.coords.longitude });
    setNotice({ type: 'info', message: 'Ubicación capturada correctamente.' });
  }

  async function capturePhoto() {
    const permission = await ImagePicker.requestCameraPermissionsAsync();

    if (permission.status !== 'granted') {
      setNotice({ type: 'error', message: 'No se concedió acceso a la cámara.' });
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
      setNotice({ type: 'error', message: 'Selecciona primero un envío visible.' });
      return;
    }

    if (!recipientName.trim()) {
      setNotice({ type: 'error', message: 'Indica el nombre del receptor.' });
      return;
    }

    if (state.settings?.requirePhoto && !photoDataUrl) {
      setNotice({ type: 'error', message: 'La configuración actual exige foto.' });
      return;
    }

    if (state.settings?.requireSignature && !signatureText.trim()) {
      setNotice({ type: 'error', message: 'La configuración actual exige firma textual.' });
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
    <Screen title="Prueba de entrega" subtitle="Captura operativa con foto, firma textual y geolocalización para la última milla.">
      <Notice type={notice.type} message={notice.message} />

      <Card accent>
        <Text style={styles.sectionTitle}>Requisitos activos</Text>
        <Text style={styles.meta}>Foto obligatoria: {state.settings?.requirePhoto ? 'Si' : 'No'}</Text>
        <Text style={styles.meta}>Firma obligatoria: {state.settings?.requireSignature ? 'Si' : 'No'}</Text>
      </Card>

      <Card>
        <Text style={styles.sectionTitle}>Envios visibles</Text>
        {state.shipments.length ? state.shipments.map((item) => {
          const active = selectedShipment?.id === item.id;

          return (
            <View key={item.id} style={styles.shipmentRow}>
              <View style={{ flex: 1 }}>
                <Pill tone={active ? 'brand' : 'soft'}>{item.status}</Pill>
                <Text style={styles.tracking}>{item.tracking}</Text>
                <Text style={styles.meta}>{item.destinationAddress || 'Sin direccion'}{item.destinationCity ? ` - ${item.destinationCity}` : ''}</Text>
              </View>
              <PrimaryButton label={active ? 'Seleccionado' : 'Usar'} onPress={() => setSelectedShipment(item)} tone={active ? 'brand' : 'outline'} />
            </View>
          );
        }) : <EmptyState title="Sin envios visibles" subtitle="Cuando tengas paquetes asignados apareceran aqui para registrar la entrega." />}
      </Card>

      <Card>
        <Text style={styles.sectionTitle}>Captura de evidencia</Text>
        <Field label="Receptor" value={recipientName} onChangeText={setRecipientName} placeholder="Nombre de quien recibe" autoCapitalize="words" />
        <Field label="Firma textual" value={signatureText} onChangeText={setSignatureText} placeholder="Nombre que quedara en la firma" autoCapitalize="words" />
        <Field label="Notas" value={notes} onChangeText={setNotes} placeholder="Observaciones de entrega" autoCapitalize="sentences" />
        <PrimaryButton label={coords ? 'Ubicacion capturada' : 'Capturar ubicacion'} onPress={captureLocation} tone="outline" />
        <PrimaryButton label={photoPreview ? 'Repetir foto' : 'Tomar foto'} onPress={capturePhoto} tone="outline" />
        {coords ? <Text style={styles.meta}>GPS: {coords.latitude.toFixed(5)}, {coords.longitude.toFixed(5)}</Text> : null}
        {photoPreview ? <Image source={{ uri: photoPreview }} style={styles.preview} /> : null}
        <PrimaryButton label={submitting ? 'Registrando...' : 'Registrar entrega'} onPress={submitEvidence} disabled={submitting} />
      </Card>
    </Screen>
  );
}

const styles = StyleSheet.create({
  sectionTitle: {
    fontSize: 18,
    fontWeight: '800',
    color: palette.text,
  },
  shipmentRow: {
    flexDirection: 'row',
    gap: spacing.md,
    alignItems: 'center',
    paddingVertical: spacing.sm,
    borderBottomWidth: 1,
    borderBottomColor: palette.line,
  },
  tracking: {
    marginTop: 8,
    fontSize: 16,
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
    backgroundColor: palette.surfaceAlt,
  },
});
