import React, { useState } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { apiRequest } from '../api';
import { Card, EmptyState, Field, LoadingState, Pill, PrimaryButton, Screen } from '../components/Ui';
import { palette } from '../theme';

export function TrackingScreen({ token }) {
  const [code, setCode] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [result, setResult] = useState(null);

  async function search() {
    if (!code.trim()) {
      setError('Ingresa un codigo de rastreo.');
      return;
    }

    setLoading(true);
    setError('');

    try {
      const response = await apiRequest(`/tracking/${encodeURIComponent(code.trim())}`, { token });
      setResult(response);
    } catch (err) {
      setResult(null);
      setError(err.message || 'No fue posible consultar el tracking.');
    } finally {
      setLoading(false);
    }
  }

  return (
    <Screen title="Rastreo" subtitle="Consulta movil de timeline y estado actual del envio.">
      <Card accent>
        <Field label="Codigo de tracking" value={code} onChangeText={setCode} placeholder="GPQ-260001" autoCapitalize="characters" />
        <PrimaryButton label={loading ? 'Buscando...' : 'Buscar'} onPress={search} disabled={loading} />
      </Card>

      {loading ? <LoadingState label="Buscando eventos..." /> : null}
      {error ? <EmptyState title="No se encontro el envio" subtitle={error} /> : null}

      {result ? (
        <>
          <Card>
            <Pill>{result.shipment.status}</Pill>
            <Text style={styles.title}>{result.shipment.tracking}</Text>
            <Text style={styles.meta}>{result.shipment.customerName}</Text>
            <Text style={styles.meta}>Destino: {result.shipment.destinationAddress || 'Sin direccion'}</Text>
          </Card>

          <Card>
            <Text style={styles.sectionTitle}>Timeline</Text>
            {result.events.length ? result.events.map((event) => (
              <View key={event.id} style={styles.timelineItem}>
                <Text style={styles.eventTitle}>{event.type}</Text>
                <Text style={styles.eventMeta}>{event.description}</Text>
                <Text style={styles.eventMeta}>{event.location}</Text>
              </View>
            )) : <Text style={styles.meta}>No hay eventos registrados.</Text>}
          </Card>
        </>
      ) : null}
    </Screen>
  );
}

const styles = StyleSheet.create({
  title: {
    fontSize: 18,
    fontWeight: '800',
    color: palette.text,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: '800',
    color: palette.text,
  },
  meta: {
    color: palette.textMuted,
  },
  timelineItem: {
    paddingTop: 10,
    borderTopWidth: 1,
    borderTopColor: palette.line,
  },
  eventTitle: {
    color: palette.text,
    fontWeight: '700',
  },
  eventMeta: {
    color: palette.textMuted,
    marginTop: 3,
  },
});
