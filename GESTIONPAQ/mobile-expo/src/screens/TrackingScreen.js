import React, { useState } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { apiRequest } from '../api';
import { Card, EmptyState, Field, LoadingState, Pill, PrimaryButton, Screen } from '../components/Ui';
import { palette, spacing, radius } from '../theme';

export function TrackingScreen({ token }) {
  const [code, setCode] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [result, setResult] = useState(null);

  async function search() {
    if (!code.trim()) {
      setError('Ingresa un código de rastreo.');
      return;
    }

    setLoading(true);
    setError('');

    try {
      const response = await apiRequest(`/tracking/${encodeURIComponent(code.trim())}`, { token });
      setResult(response);
    } catch (err) {
      setResult(null);
      setError(err.message || 'No fue posible consultar el rastreo.');
    } finally {
      setLoading(false);
    }
  }

  return (
    <Screen title="Rastreo" subtitle="Consulta móvil de línea de tiempo y estado actual del envío.">
      <Card accent>
        <Field label="Código de rastreo" value={code} onChangeText={setCode} placeholder="GPQ-260001" autoCapitalize="characters" />
        <PrimaryButton label={loading ? 'Buscando...' : 'Buscar'} onPress={search} disabled={loading} />
      </Card>

      {loading ? <LoadingState label="Buscando eventos..." /> : null}
      {error ? <EmptyState title="No se encontró el envío" subtitle={error} /> : null}

      {result ? (
        <>
          <Card>
            <Pill>{result.shipment.status}</Pill>
            <Text style={styles.title}>{result.shipment.tracking}</Text>
            <Text style={styles.meta}>{result.shipment.customerName}</Text>
            <Text style={styles.meta}>Destino: {result.shipment.destinationAddress || 'Sin dirección'}</Text>
          </Card>

          <Card>
            <Text style={styles.sectionTitle}>Línea de tiempo</Text>
            {result.events.length ? result.events.map((event, i) => (
              <View key={event.id} style={styles.timelineItem}>
                <View style={styles.timelineDotCol}>
                  <View style={[styles.timelineDot, i === 0 && styles.timelineDotActive]} />
                  {i < result.events.length - 1 && <View style={styles.timelineLine} />}
                </View>
                <View style={styles.timelineContent}>
                  <Text style={styles.eventTitle}>{event.type}</Text>
                  <Text style={styles.eventMeta}>{event.description}</Text>
                  {event.location ? <Text style={styles.eventLocation}>{event.location}</Text> : null}
                </View>
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
    fontSize: 17,
    fontWeight: '800',
    color: palette.text,
    letterSpacing: -0.2,
  },
  sectionTitle: {
    fontSize: 15,
    fontWeight: '700',
    color: palette.text,
  },
  meta: {
    color: palette.textMuted,
    fontSize: 13,
  },
  timelineItem: {
    flexDirection: 'row',
    gap: spacing.sm,
    paddingTop: spacing.sm,
  },
  timelineDotCol: {
    alignItems: 'center',
    width: 16,
    paddingTop: 3,
  },
  timelineDot: {
    width: 10,
    height: 10,
    borderRadius: 5,
    backgroundColor: palette.line,
    borderWidth: 2,
    borderColor: palette.textLight,
  },
  timelineDotActive: {
    backgroundColor: palette.brand,
    borderColor: palette.brand,
  },
  timelineLine: {
    width: 2,
    flex: 1,
    backgroundColor: palette.line,
    marginTop: 4,
    minHeight: 20,
  },
  timelineContent: {
    flex: 1,
    gap: 2,
    paddingBottom: spacing.sm,
  },
  eventTitle: {
    color: palette.text,
    fontWeight: '700',
    fontSize: 13,
  },
  eventMeta: {
    color: palette.textMuted,
    fontSize: 12,
    lineHeight: 17,
  },
  eventLocation: {
    color: palette.brand,
    fontSize: 11,
    fontWeight: '600',
  },
});
