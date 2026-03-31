import React, { useState } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { apiRequest } from '../api';
import { Card, EmptyState, Field, LoadingState, Pill, PrimaryButton, Screen } from '../components/Ui';
import { palette, spacing, toneForStatus } from '../theme';

function eventTimestamp(event) {
  return event.timestampEvent || event.timestamp || event.fecha || event.createdAt || '';
}

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
    <Screen eyebrow="Consulta puntual" title="Rastreo" subtitle="Consulta movil de timeline y estado actual del envio.">
      <Card tone="dark">
        <Text style={styles.heroTitle}>Seguimiento unificado</Text>
        <Text style={styles.heroCopy}>Consulta codigo, estado, destino y eventos con una lectura más clara para operación y clientes.</Text>
      </Card>

      <Card tone="soft">
        <Field label="Codigo de tracking" value={code} onChangeText={setCode} placeholder="GPQ-260001" autoCapitalize="characters" />
        <PrimaryButton label={loading ? 'Buscando...' : 'Buscar'} onPress={search} disabled={loading} />
      </Card>

      {loading ? <LoadingState label="Buscando eventos..." /> : null}
      {error ? <EmptyState title="No se encontro el envio" subtitle={error} /> : null}

      {result ? (
        <>
          <Card tone="dark">
            <Pill tone={toneForStatus(result.shipment.status)}>{result.shipment.status}</Pill>
            <Text style={styles.title}>{result.shipment.tracking}</Text>
            <Text style={styles.metaDark}>{result.shipment.customerName}</Text>
            <Text style={styles.metaDark}>Destino: {result.shipment.destinationAddress || 'Sin direccion'}</Text>
            <View style={styles.summaryRow}>
              <View style={styles.summaryTile}>
                <Text style={styles.summaryLabel}>Ruta</Text>
                <Text style={styles.summaryValue}>{result.shipment.routeCode || 'Pendiente'}</Text>
              </View>
              <View style={styles.summaryTile}>
                <Text style={styles.summaryLabel}>Conductor</Text>
                <Text style={styles.summaryValue}>{result.shipment.driverName || 'Pendiente'}</Text>
              </View>
            </View>
          </Card>

          <Card>
            <Text style={styles.sectionTitle}>Timeline</Text>
            {result.events.length ? result.events.map((event, index) => (
              <View key={event.id} style={styles.timelineItem}>
                <View style={styles.timelineRail}>
                  <View style={styles.timelineDot} />
                  {index < result.events.length - 1 ? <View style={styles.timelineLine} /> : null}
                </View>
                <View style={styles.timelineBody}>
                  <Text style={styles.eventTitle}>{event.type}</Text>
                  <Text style={styles.eventMeta}>{event.description}</Text>
                  {event.location ? <Text style={styles.eventFoot}>{event.location}</Text> : null}
                  {eventTimestamp(event) ? <Text style={styles.eventTime}>{eventTimestamp(event)}</Text> : null}
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
  heroTitle: {
    color: palette.textOnDark,
    fontSize: 24,
    fontWeight: '800',
  },
  heroCopy: {
    color: palette.textMutedOnDark,
    lineHeight: 20,
  },
  title: {
    fontSize: 22,
    fontWeight: '800',
    color: palette.textOnDark,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: '800',
    color: palette.text,
  },
  meta: {
    color: palette.textMuted,
  },
  metaDark: {
    color: palette.textMutedOnDark,
  },
  summaryRow: {
    flexDirection: 'row',
    gap: spacing.sm,
    marginTop: spacing.xs,
  },
  summaryTile: {
    flex: 1,
    padding: spacing.sm,
    borderRadius: 16,
    backgroundColor: 'rgba(255, 255, 255, 0.08)',
  },
  summaryLabel: {
    color: palette.textMutedOnDark,
    fontSize: 11,
    textTransform: 'uppercase',
    letterSpacing: 0.8,
    fontWeight: '700',
  },
  summaryValue: {
    marginTop: 6,
    color: palette.textOnDark,
    fontWeight: '800',
  },
  timelineItem: {
    flexDirection: 'row',
    alignItems: 'stretch',
    gap: spacing.sm,
    paddingTop: 12,
    borderTopWidth: 1,
    borderTopColor: palette.line,
  },
  timelineRail: {
    width: 18,
    alignItems: 'center',
  },
  timelineDot: {
    width: 10,
    height: 10,
    borderRadius: 5,
    marginTop: 4,
    backgroundColor: palette.brand,
  },
  timelineLine: {
    flex: 1,
    width: 2,
    marginTop: 6,
    backgroundColor: 'rgba(15, 123, 108, 0.16)',
  },
  timelineBody: {
    flex: 1,
    gap: 2,
  },
  eventTitle: {
    color: palette.text,
    fontWeight: '700',
  },
  eventMeta: {
    color: palette.textMuted,
    marginTop: 2,
  },
  eventFoot: {
    color: palette.text,
    fontSize: 12,
    marginTop: 4,
  },
  eventTime: {
    color: palette.textSoft,
    fontSize: 12,
    marginTop: 4,
  },
});
