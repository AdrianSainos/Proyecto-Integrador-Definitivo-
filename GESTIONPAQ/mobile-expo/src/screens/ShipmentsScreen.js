import React, { useEffect, useState } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { apiRequest } from '../api';
import { Card, EmptyState, LoadingState, Pill, Screen } from '../components/Ui';
import { palette, spacing, toneForStatus } from '../theme';

export function ShipmentsScreen({ token, user }) {
  const [state, setState] = useState({ loading: true, error: '', items: [] });

  useEffect(() => {
    (async () => {
      try {
        const items = await apiRequest('/shipments', { token });
        setState({ loading: false, error: '', items });
      } catch (error) {
        setState({ loading: false, error: error.message, items: [] });
      }
    })();
  }, [token]);

  if (state.loading) {
    return <LoadingState label="Cargando envios..." />;
  }

  if (state.error) {
    return <Screen title="Envios" subtitle="Vista movil de paquetes y asignaciones."><EmptyState title="Sin respuesta del backend" subtitle={state.error} /></Screen>;
  }

  const items = state.items || [];
  const inOperation = items.filter((item) => ['brand', 'info'].includes(toneForStatus(item.status))).length;
  const pending = items.filter((item) => toneForStatus(item.status) === 'accent').length;
  const delivered = items.filter((item) => toneForStatus(item.status) === 'success').length;

  return (
    <Screen eyebrow="Control de envios" title="Envios" subtitle={user.role === 'customer' ? 'Consulta de tus envios visibles.' : 'Listado movil con estado, ruta, unidad y conductor.'}>
      <Card tone="dark">
        <Text style={styles.heroTitle}>Visibilidad de paquetes</Text>
        <Text style={styles.heroCopy}>Lectura más clara de estado, asignación y recursos sobre el mismo lenguaje visual del panel web.</Text>
        <View style={styles.summaryRow}>
          <View style={styles.summaryTile}>
            <Text style={styles.summaryValue}>{items.length}</Text>
            <Text style={styles.summaryLabel}>Totales</Text>
          </View>
          <View style={styles.summaryTile}>
            <Text style={styles.summaryValue}>{inOperation}</Text>
            <Text style={styles.summaryLabel}>Operando</Text>
          </View>
          <View style={styles.summaryTile}>
            <Text style={styles.summaryValue}>{pending + delivered}</Text>
            <Text style={styles.summaryLabel}>Pendientes + cierre</Text>
          </View>
        </View>
      </Card>

      {items.length ? items.map((item) => {
        const tone = toneForStatus(item.status);

        return (
          <Card key={item.id} tone={tone === 'neutral' ? 'default' : 'soft'} style={styles.shipmentCard}>
            <View style={styles.cardTop}>
              <View style={styles.cardHeaderCopy}>
                <Pill tone={tone}>{item.status}</Pill>
                <Text style={styles.title}>{item.tracking}</Text>
                <Text style={styles.customer}>{item.customerName || 'Cliente sin nombre'}</Text>
              </View>

              <View style={[styles.routeFlag, item.routeCode ? styles.routeFlagReady : styles.routeFlagPending]}>
                <Text style={[styles.routeFlagLabel, item.routeCode ? styles.routeFlagLabelReady : styles.routeFlagLabelPending]}>{item.routeCode || 'Sin ruta'}</Text>
              </View>
            </View>

            <Text style={styles.destination}>{item.destinationAddress || 'Destino sin direccion registrada.'}</Text>

            <View style={styles.metaGrid}>
              <View style={styles.metaTile}>
                <Text style={styles.metaLabel}>Vehiculo</Text>
                <Text style={styles.metaValue}>{item.vehiclePlate || 'Pendiente'}</Text>
              </View>
              <View style={styles.metaTile}>
                <Text style={styles.metaLabel}>Conductor</Text>
                <Text style={styles.metaValue}>{item.driverName || 'Pendiente'}</Text>
              </View>
              <View style={styles.metaTile}>
                <Text style={styles.metaLabel}>Programado</Text>
                <Text style={styles.metaValue}>{item.scheduledDate || 'Sin fecha'}</Text>
              </View>
              <View style={styles.metaTile}>
                <Text style={styles.metaLabel}>Prioridad</Text>
                <Text style={styles.metaValue}>{item.priority || 'Estandar'}</Text>
              </View>
            </View>
          </Card>
        );
      }) : (
        <EmptyState title="Sin envios visibles" subtitle="Cuando existan envios asociados a tu cuenta o rol, apareceran aqui." />
      )}
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
  summaryRow: {
    flexDirection: 'row',
    gap: spacing.sm,
    marginTop: spacing.xs,
  },
  summaryTile: {
    flex: 1,
    padding: spacing.sm,
    borderRadius: 18,
    backgroundColor: 'rgba(255, 255, 255, 0.08)',
  },
  summaryValue: {
    color: palette.textOnDark,
    fontSize: 22,
    fontWeight: '800',
  },
  summaryLabel: {
    marginTop: 4,
    color: palette.textMutedOnDark,
    fontSize: 12,
  },
  shipmentCard: {
    gap: spacing.sm,
  },
  cardTop: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    justifyContent: 'space-between',
    gap: spacing.sm,
  },
  cardHeaderCopy: {
    flex: 1,
    gap: spacing.xs,
  },
  title: {
    fontSize: 22,
    fontWeight: '800',
    color: palette.text,
  },
  customer: {
    color: palette.textMuted,
  },
  destination: {
    color: palette.text,
    lineHeight: 20,
  },
  routeFlag: {
    paddingHorizontal: 12,
    paddingVertical: 10,
    borderRadius: 16,
    borderWidth: 1,
  },
  routeFlagReady: {
    backgroundColor: 'rgba(15, 123, 108, 0.1)',
    borderColor: 'rgba(15, 123, 108, 0.16)',
  },
  routeFlagPending: {
    backgroundColor: 'rgba(255, 138, 61, 0.12)',
    borderColor: 'rgba(255, 138, 61, 0.18)',
  },
  routeFlagLabel: {
    fontSize: 12,
    fontWeight: '800',
  },
  routeFlagLabelReady: {
    color: palette.brandDeep,
  },
  routeFlagLabelPending: {
    color: palette.accentDeep,
  },
  metaGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
  },
  metaTile: {
    flexGrow: 1,
    minWidth: '47%',
    padding: spacing.sm,
    borderRadius: 16,
    backgroundColor: 'rgba(255, 255, 255, 0.62)',
    borderWidth: 1,
    borderColor: 'rgba(26, 38, 62, 0.06)',
  },
  metaLabel: {
    color: palette.textMuted,
    fontSize: 11,
    textTransform: 'uppercase',
    letterSpacing: 0.8,
    fontWeight: '700',
  },
  metaValue: {
    marginTop: 6,
    color: palette.text,
    fontWeight: '700',
  },
});