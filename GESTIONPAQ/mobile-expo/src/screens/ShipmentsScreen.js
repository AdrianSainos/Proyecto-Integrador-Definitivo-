import React, { useEffect, useState } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { apiRequest } from '../api';
import { Card, EmptyState, LoadingState, Pill, Screen } from '../components/Ui';
import { palette, spacing } from '../theme';

const STATUS_LABELS = { scheduled: 'Programado', in_progress: 'En progreso', completed: 'Completado', cancelled: 'Cancelado', canceled: 'Cancelado', delivered: 'Entregado', evidence_recorded: 'Evidencia registrada', pending: 'Pendiente', active: 'Activo', in_transit: 'En tránsito', failed: 'Fallido', assigned: 'Asignado' };
const STATUS_TONES = { completed: 'success', delivered: 'success', evidence_recorded: 'success', active: 'success', in_progress: 'info', in_transit: 'info', scheduled: 'brand', assigned: 'brand', pending: 'soft', cancelled: 'danger', canceled: 'danger', failed: 'danger' };
function statusLabel(v) { return STATUS_LABELS[String(v || '').toLowerCase().trim()] || v || ''; }
function statusTone(v) { return STATUS_TONES[String(v || '').toLowerCase().trim()] || 'neutral'; }

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
    return <LoadingState label="Cargando envíos..." />;
  }

  if (state.error) {
    return <Screen title="Envíos" subtitle="Vista móvil de paquetes y asignaciones."><EmptyState title="Sin respuesta del servidor" subtitle={state.error} /></Screen>;
  }

  return (
    <Screen title="Envíos" subtitle={user.role === 'customer' ? 'Consulta de tus envíos visibles.' : 'Listado móvil con estado, ruta y conductor.'}>
      {state.items.length ? state.items.map((item) => (
        <Card key={item.id}>
          <Pill tone={statusTone(item.status)}>{statusLabel(item.status)}</Pill>
          <Text style={styles.title}>{item.tracking}</Text>
          <View style={styles.metaGrid}>
            <Text style={styles.metaKey}>Cliente</Text>
            <Text style={styles.metaVal}>{item.customerName || '—'}</Text>
            <Text style={styles.metaKey}>Ruta</Text>
            <Text style={styles.metaVal}>{item.routeCode || 'Pendiente'}</Text>
            <Text style={styles.metaKey}>Vehículo</Text>
            <Text style={styles.metaVal}>{item.vehiclePlate || 'Pendiente'}</Text>
            <Text style={styles.metaKey}>Conductor</Text>
            <Text style={styles.metaVal}>{item.driverName || 'Pendiente'}</Text>
          </View>
        </Card>
      )) : <EmptyState title="Sin envíos visibles" subtitle="Cuando existan envíos asociados a tu cuenta o rol, aparecerán aquí." />}
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