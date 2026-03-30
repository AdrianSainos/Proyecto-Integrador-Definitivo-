import React, { useEffect, useState } from 'react';
import { Text, View, StyleSheet } from 'react-native';
import { apiRequest } from '../api';
import { Card, EmptyState, LoadingState, Pill, Screen, StatCard } from '../components/Ui';
import { palette } from '../theme';

export function HomeScreen({ token, user }) {
  const [state, setState] = useState({ loading: true, error: '', data: null });

  useEffect(() => {
    (async () => {
      try {
        const data = await apiRequest('/dashboard?range=week', { token });
        setState({ loading: false, error: '', data });
      } catch (error) {
        setState({ loading: false, error: error.message, data: null });
      }
    })();
  }, [token]);

  if (state.loading) {
    return <LoadingState label="Consultando tablero movil..." />;
  }

  if (state.error) {
    return <Screen title="Inicio" subtitle="No fue posible cargar el tablero."><EmptyState title="Error de conexion" subtitle={state.error} /></Screen>;
  }

  const data = state.data;

  return (
    <Screen title="Inicio" subtitle={`Resumen movil para ${user.role}.`}>
      <Card accent>
        <Pill>{data.range.label}</Pill>
        <Text style={styles.heroTitle}>Operacion sincronizada</Text>
        <Text style={styles.heroCopy}>Resumen del periodo, excepciones recientes y avance de la operacion.</Text>
      </Card>

      {data.kpis.map((item) => (
        <StatCard key={item.title} label={item.title} value={item.value} detail={item.detail} />
      ))}

      <Card>
        <Text style={styles.sectionTitle}>Excepciones</Text>
        {data.exceptions.pendingDeparture.length ? data.exceptions.pendingDeparture.map((item) => (
          <View key={item.id} style={styles.row}>
            <Text style={styles.rowTitle}>{item.tracking}</Text>
            <Text style={styles.rowMeta}>{item.status}</Text>
          </View>
        )) : <Text style={styles.emptyText}>Sin incidencias visibles.</Text>}
      </Card>
    </Screen>
  );
}

const styles = StyleSheet.create({
  heroTitle: {
    color: palette.text,
    fontSize: 24,
    fontWeight: '800',
  },
  heroCopy: {
    color: palette.textMuted,
    lineHeight: 20,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: '800',
    color: palette.text,
  },
  row: {
    paddingTop: 10,
    borderTopWidth: 1,
    borderTopColor: palette.line,
  },
  rowTitle: {
    color: palette.text,
    fontWeight: '700',
  },
  rowMeta: {
    color: palette.textMuted,
    marginTop: 4,
  },
  emptyText: {
    color: palette.textMuted,
  },
});