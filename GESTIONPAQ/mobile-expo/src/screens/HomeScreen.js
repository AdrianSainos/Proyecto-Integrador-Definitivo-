import React, { useEffect, useState } from 'react';
import { Text, View, StyleSheet } from 'react-native';
import { apiRequest } from '../api';
import { Card, EmptyState, LoadingState, Pill, Screen, StatCard } from '../components/Ui';
import { palette, spacing } from '../theme';

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
    return <LoadingState label="Consultando tablero móvil..." />;
  }

  if (state.error) {
    return <Screen title="Inicio" subtitle="No fue posible cargar el tablero."><EmptyState title="Error de conexión" subtitle={state.error} /></Screen>;
  }

  const data = state.data;

  return (
    <Screen title="Inicio" subtitle={`Resumen móvil · ${data.range.label}`}>
      <Card accent>
        <Pill tone="brand">{data.range.label}</Pill>
        <Text style={styles.heroTitle}>Operación en tiempo real</Text>
        <Text style={styles.heroCopy}>Resumen del período, excepciones recientes y avance de la operación.</Text>
      </Card>

      {data.kpis.map((item) => (
        <StatCard key={item.title} label={item.title} value={item.value} detail={item.detail} />
      ))}

      <Card>
        <Text style={styles.sectionTitle}>Excepciones recientes</Text>
        {data.exceptions.pendingDeparture.length ? data.exceptions.pendingDeparture.map((item) => (
          <View key={item.id} style={styles.row}>
            <Text style={styles.rowTitle}>{item.tracking}</Text>
            <Text style={styles.rowMeta}>{item.status}</Text>
          </View>
        )) : (
          <View style={styles.noExceptions}>
            <Text style={styles.noExceptionsText}>✓ Sin incidencias visibles</Text>
          </View>
        )}
      </Card>
    </Screen>
  );
}

const styles = StyleSheet.create({
  heroTitle: {
    color: palette.text,
    fontSize: 20,
    fontWeight: '800',
    letterSpacing: -0.3,
  },
  heroCopy: {
    color: palette.textMuted,
    fontSize: 13,
    lineHeight: 19,
  },
  sectionTitle: {
    fontSize: 15,
    fontWeight: '700',
    color: palette.text,
    letterSpacing: -0.1,
  },
  row: {
    paddingTop: spacing.sm,
    borderTopWidth: 1,
    borderTopColor: palette.line,
    gap: 3,
  },
  rowTitle: {
    color: palette.text,
    fontWeight: '700',
    fontSize: 14,
  },
  rowMeta: {
    color: palette.textMuted,
    fontSize: 12,
  },
  noExceptions: {
    paddingVertical: spacing.sm,
    alignItems: 'center',
  },
  noExceptionsText: {
    color: palette.success,
    fontWeight: '600',
    fontSize: 13,
  },
});