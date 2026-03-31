import React, { useEffect, useState } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { apiRequest } from '../api';
import { Card, EmptyState, LoadingState, Pill, Screen } from '../components/Ui';
import { palette, spacing, toneForStatus } from '../theme';

export function RoutesScreen({ token, user }) {
  const [state, setState] = useState({ loading: true, error: '', items: [] });

  useEffect(() => {
    (async () => {
      try {
        const items = await apiRequest('/routes', { token });
        setState({ loading: false, error: '', items });
      } catch (error) {
        setState({ loading: false, error: error.message, items: [] });
      }
    })();
  }, [token]);

  if (state.loading) {
    return <LoadingState label="Cargando rutas..." />;
  }

  if (state.error) {
    return <Screen title="Rutas" subtitle="Planificacion y ejecucion movil."><EmptyState title="No fue posible cargar rutas" subtitle={state.error} /></Screen>;
  }

  const items = state.items || [];
  const activeRoutes = items.filter((item) => ['brand', 'info'].includes(toneForStatus(item.status))).length;
  const withDriver = items.filter((item) => Boolean(item.driverName)).length;
  const withVehicle = items.filter((item) => Boolean(item.vehiclePlate)).length;

  return (
    <Screen eyebrow="Planificacion movil" title="Rutas" subtitle={user.role === 'driver' ? 'Rutas visibles para tu jornada.' : 'Vista movil de asignacion, unidad y distancia.'}>
      <Card tone="dark">
        <Text style={styles.heroTitle}>Control de rutas</Text>
        <Text style={styles.heroCopy}>Visualiza estado operativo, unidad y conductor en un formato más cercano al dashboard web.</Text>
        <View style={styles.summaryRow}>
          <View style={styles.summaryTile}>
            <Text style={styles.summaryValue}>{items.length}</Text>
            <Text style={styles.summaryLabel}>Totales</Text>
          </View>
          <View style={styles.summaryTile}>
            <Text style={styles.summaryValue}>{activeRoutes}</Text>
            <Text style={styles.summaryLabel}>Activas</Text>
          </View>
          <View style={styles.summaryTile}>
            <Text style={styles.summaryValue}>{withDriver}/{withVehicle}</Text>
            <Text style={styles.summaryLabel}>Chofer / unidad</Text>
          </View>
        </View>
      </Card>

      {items.length ? items.map((item) => {
        const tone = toneForStatus(item.status);

        return (
          <Card key={item.id} tone={tone === 'neutral' ? 'default' : 'soft'} style={styles.routeCard}>
            <View style={styles.cardTop}>
              <View style={styles.cardHeaderCopy}>
                <Pill tone={tone}>{item.status}</Pill>
                <Text style={styles.title}>{item.code}</Text>
                <Text style={styles.origin}>Salida: {item.warehouseName || 'Sin almacen asignado'}</Text>
              </View>
              <Pill tone="soft">{item.scheduledDate || 'Sin fecha'}</Pill>
            </View>

            <View style={styles.metricRow}>
              <View style={styles.metricTile}>
                <Text style={styles.metricLabel}>Distancia</Text>
                <Text style={styles.metricValue}>{item.distanceKm} km</Text>
              </View>
              <View style={styles.metricTile}>
                <Text style={styles.metricLabel}>Tiempo</Text>
                <Text style={styles.metricValue}>{item.timeMinutes} min</Text>
              </View>
            </View>

            <View style={styles.detailRow}>
              <Text style={styles.detailLabel}>Vehiculo</Text>
              <Text style={styles.detailValue}>{item.vehiclePlate || 'Pendiente'}</Text>
            </View>
            <View style={styles.detailRow}>
              <Text style={styles.detailLabel}>Conductor</Text>
              <Text style={styles.detailValue}>{item.driverName || 'Pendiente'}</Text>
            </View>
          </Card>
        );
      }) : (
        <EmptyState title="Sin rutas visibles" subtitle="El backend mostrara aqui las rutas asociadas al rol autenticado." />
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
  routeCard: {
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
  origin: {
    color: palette.textMuted,
  },
  metricRow: {
    flexDirection: 'row',
    gap: spacing.sm,
  },
  metricTile: {
    flex: 1,
    padding: spacing.sm,
    borderRadius: 16,
    backgroundColor: 'rgba(255, 255, 255, 0.62)',
    borderWidth: 1,
    borderColor: 'rgba(26, 38, 62, 0.06)',
  },
  metricLabel: {
    color: palette.textMuted,
    fontSize: 11,
    textTransform: 'uppercase',
    letterSpacing: 0.8,
    fontWeight: '700',
  },
  metricValue: {
    marginTop: 6,
    color: palette.text,
    fontWeight: '800',
  },
  detailRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: spacing.sm,
    paddingTop: 12,
    borderTopWidth: 1,
    borderTopColor: palette.line,
  },
  detailLabel: {
    color: palette.textMuted,
    fontWeight: '700',
  },
  detailValue: {
    color: palette.text,
    fontWeight: '700',
  },
});