import React, { useEffect, useState } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { apiRequest } from '../api';
import { Card, EmptyState, LoadingState, Pill, Screen } from '../components/Ui';
import { palette, spacing } from '../theme';

const STATUS_LABELS = { scheduled: 'Programado', in_progress: 'En progreso', completed: 'Completado', cancelled: 'Cancelado', canceled: 'Cancelado', delivered: 'Entregado', pending: 'Pendiente', active: 'Activo', in_transit: 'En tránsito', failed: 'Fallido', assigned: 'Asignado' };
const STATUS_TONES = { completed: 'success', delivered: 'success', active: 'success', in_progress: 'info', in_transit: 'info', scheduled: 'brand', assigned: 'brand', pending: 'soft', cancelled: 'danger', canceled: 'danger', failed: 'danger' };
function statusLabel(v) { return STATUS_LABELS[String(v || '').toLowerCase().trim()] || v || ''; }
function statusTone(v) { return STATUS_TONES[String(v || '').toLowerCase().trim()] || 'neutral'; }

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
    return <Screen title="Rutas" subtitle="Planificación y ejecución móvil."><EmptyState title="No fue posible cargar rutas" subtitle={state.error} /></Screen>;
  }

  return (
    <Screen title="Rutas" subtitle={user.role === 'driver' ? 'Rutas visibles para tu jornada.' : 'Vista móvil de asignación, unidad y distancia.'}>
      {state.items.length ? state.items.map((item) => (
        <Card key={item.id}>
          <Pill tone={statusTone(item.status)}>{statusLabel(item.status)}</Pill>
          <Text style={styles.title}>{item.code}</Text>
          <View style={styles.metaGrid}>
            <Text style={styles.metaKey}>Origen</Text>
            <Text style={styles.metaVal}>{item.warehouseName || '—'}</Text>
            <Text style={styles.metaKey}>Distancia / Tiempo</Text>
            <Text style={styles.metaVal}>{item.distanceKm} km · {item.timeMinutes} min</Text>
            <Text style={styles.metaKey}>Vehículo</Text>
            <Text style={styles.metaVal}>{item.vehiclePlate || 'Pendiente'}</Text>
            <Text style={styles.metaKey}>Conductor</Text>
            <Text style={styles.metaVal}>{item.driverName || 'Pendiente'}</Text>
          </View>
        </Card>
      )) : <EmptyState title="Sin rutas visibles" subtitle="El servidor mostrará aquí las rutas asociadas al rol autenticado." />}
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
  metaGrid: {
    gap: 4,
    borderTopWidth: 1,
    borderTopColor: palette.line,
    paddingTop: spacing.sm,
    marginTop: 2,
  },
  metaKey: {
    color: palette.textLight,
    fontSize: 10,
    fontWeight: '700',
    textTransform: 'uppercase',
    letterSpacing: 0.8,
    marginTop: 6,
  },
  metaVal: {
    color: palette.text,
    fontSize: 13,
    fontWeight: '500',
  },
});