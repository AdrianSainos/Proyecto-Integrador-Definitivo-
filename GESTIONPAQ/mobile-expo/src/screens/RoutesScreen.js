import React, { useEffect, useState } from 'react';
import { StyleSheet, Text } from 'react-native';
import { apiRequest } from '../api';
import { Card, EmptyState, LoadingState, Pill, Screen } from '../components/Ui';
import { palette } from '../theme';

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

  return (
    <Screen title="Rutas" subtitle={user.role === 'driver' ? 'Rutas visibles para tu jornada.' : 'Vista movil de asignacion, unidad y distancia.'}>
      {state.items.length ? state.items.map((item) => (
        <Card key={item.id}>
          <Pill>{item.status}</Pill>
          <Text style={styles.title}>{item.code}</Text>
          <Text style={styles.meta}>Origen: {item.warehouseName}</Text>
          <Text style={styles.meta}>Distancia: {item.distanceKm} km</Text>
          <Text style={styles.meta}>Tiempo estimado: {item.timeMinutes} min</Text>
          <Text style={styles.meta}>Vehiculo: {item.vehiclePlate || 'Pendiente'}</Text>
          <Text style={styles.meta}>Conductor: {item.driverName || 'Pendiente'}</Text>
        </Card>
      )) : <EmptyState title="Sin rutas visibles" subtitle="El backend mostrara aqui las rutas asociadas al rol autenticado." />}
    </Screen>
  );
}

const styles = StyleSheet.create({
  title: {
    fontSize: 18,
    fontWeight: '800',
    color: palette.text,
  },
  meta: {
    color: palette.textMuted,
  },
});