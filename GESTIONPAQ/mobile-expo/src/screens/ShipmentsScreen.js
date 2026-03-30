import React, { useEffect, useState } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { apiRequest } from '../api';
import { Card, EmptyState, LoadingState, Pill, Screen } from '../components/Ui';
import { palette } from '../theme';

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

  return (
    <Screen title="Envios" subtitle={user.role === 'customer' ? 'Consulta de tus envios visibles.' : 'Listado movil con estado, ruta y conductor.'}>
      {state.items.length ? state.items.map((item) => (
        <Card key={item.id}>
          <Pill tone="soft">{item.status}</Pill>
          <Text style={styles.title}>{item.tracking}</Text>
          <Text style={styles.meta}>{item.customerName}</Text>
          <Text style={styles.meta}>Ruta: {item.routeCode || 'Pendiente'}</Text>
          <Text style={styles.meta}>Vehiculo: {item.vehiclePlate || 'Pendiente'}</Text>
          <Text style={styles.meta}>Conductor: {item.driverName || 'Pendiente'}</Text>
        </Card>
      )) : <EmptyState title="Sin envios visibles" subtitle="Cuando existan envios asociados a tu cuenta o rol, apareceran aqui." />}
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