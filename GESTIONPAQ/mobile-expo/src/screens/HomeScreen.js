import React, { useEffect, useState } from 'react';
import { Text, View, StyleSheet } from 'react-native';
import { apiRequest } from '../api';
import { Card, EmptyState, LoadingState, Pill, Screen, StatCard } from '../components/Ui';
import { palette, roleLabel, spacing, toneForStatus } from '../theme';

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
  const kpis = Array.isArray(data.kpis) ? data.kpis : [];
  const pendingDeparture = Array.isArray(data.exceptions?.pendingDeparture) ? data.exceptions.pendingDeparture : [];
  const exceptionCount = Object.values(data.exceptions || {}).reduce(
    (total, bucket) => total + (Array.isArray(bucket) ? bucket.length : 0),
    0,
  );

  return (
    <Screen eyebrow="Vista ejecutiva" title="Inicio" subtitle={`Resumen operativo para ${roleLabel(user.role)}.`}>
      <Card tone="dark">
        <Pill tone="dark">{data.range?.label || 'Semana actual'}</Pill>
        <Text style={styles.heroTitle}>Operacion sincronizada</Text>
        <Text style={styles.heroCopy}>Resumen del periodo, desvíos visibles y avance de la red con una lectura más ejecutiva.</Text>
        <View style={styles.heroMetrics}>
          {kpis.slice(0, 2).map((item) => (
            <View key={item.title} style={styles.heroMetricItem}>
              <Text style={styles.heroMetricValue}>{item.value}</Text>
              <Text style={styles.heroMetricLabel}>{item.title}</Text>
            </View>
          ))}
        </View>
      </Card>

      {kpis.length ? (
        <View style={styles.kpiGrid}>
          {kpis.map((item, index) => (
            <StatCard
              key={item.title}
              label={item.title}
              value={item.value}
              detail={item.detail}
              tone={['brand', 'accent', 'info', 'success'][index % 4]}
            />
          ))}
        </View>
      ) : null}

      <Card>
        <View style={styles.sectionHeader}>
          <Text style={styles.sectionTitle}>Excepciones</Text>
          <Pill tone={exceptionCount ? 'accent' : 'success'}>{exceptionCount ? `${exceptionCount} abiertas` : 'Sin alertas'}</Pill>
        </View>
        {pendingDeparture.length ? pendingDeparture.map((item) => (
          <View key={item.id} style={styles.row}>
            <View style={styles.rowCopy}>
              <Text style={styles.rowTitle}>{item.tracking}</Text>
              <Text style={styles.rowMeta}>{item.routeCode ? `Ruta ${item.routeCode}` : 'Movimiento pendiente de salida.'}</Text>
            </View>
            <Pill tone={toneForStatus(item.status)}>{item.status}</Pill>
          </View>
        )) : <Text style={styles.emptyText}>Sin incidencias visibles.</Text>}
      </Card>
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
  heroMetrics: {
    flexDirection: 'row',
    gap: spacing.sm,
    marginTop: spacing.xs,
  },
  heroMetricItem: {
    flex: 1,
    padding: spacing.sm,
    borderRadius: 18,
    backgroundColor: 'rgba(255, 255, 255, 0.08)',
  },
  heroMetricValue: {
    color: palette.textOnDark,
    fontSize: 22,
    fontWeight: '800',
  },
  heroMetricLabel: {
    marginTop: 4,
    color: palette.textMutedOnDark,
    fontSize: 12,
  },
  kpiGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
  },
  sectionHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: spacing.sm,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: '800',
    color: palette.text,
  },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: spacing.sm,
    paddingTop: 12,
    borderTopWidth: 1,
    borderTopColor: palette.line,
  },
  rowCopy: {
    flex: 1,
    gap: 4,
  },
  rowTitle: {
    color: palette.text,
    fontWeight: '700',
  },
  rowMeta: {
    color: palette.textMuted,
  },
  emptyText: {
    color: palette.textMuted,
  },
});