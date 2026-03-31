import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { Card, Pill, PrimaryButton, Screen } from '../components/Ui';
import { palette, spacing, shadow, radius } from '../theme';

export function ProfileScreen({ user, onLogout }) {
  const ROLE_LABELS = { admin: 'Administrador', operator: 'Operador', supervisor: 'Supervisor', dispatcher: 'Despachador', driver: 'Conductor', customer: 'Cliente' };
  const initials = (user.name || '?').split(' ').slice(0, 2).map((w) => w[0]).join('').toUpperCase();

  return (
    <Screen title="Mi perfil" subtitle="Sesión activa y configuración de cuenta.">
      <View style={styles.profileHero}>
        <View style={styles.avatarLarge}>
          <Text style={styles.avatarText}>{initials}</Text>
        </View>
        <Text style={styles.name}>{user.name}</Text>
        <Pill tone="brand">{ROLE_LABELS[user.role] || user.role}</Pill>
      </View>

      <Card>
        <View style={styles.infoRow}>
          <Text style={styles.infoKey}>Usuario</Text>
          <Text style={styles.infoVal}>{user.username ? `@${user.username}` : '—'}</Text>
        </View>
        <View style={styles.infoRow}>
          <Text style={styles.infoKey}>Correo</Text>
          <Text style={styles.infoVal}>{user.email || '—'}</Text>
        </View>
        <View style={styles.infoRow}>
          <Text style={styles.infoKey}>Puesto</Text>
          <Text style={styles.infoVal}>{user.jobTitle || 'Sin puesto asignado'}</Text>
        </View>
        <View style={[styles.infoRow, styles.infoRowLast]}>
          <Text style={styles.infoKey}>Horario</Text>
          <Text style={styles.infoVal}>{user.schedule || 'Sin horario base'}</Text>
        </View>
      </Card>

      <PrimaryButton label="Cerrar sesión" onPress={onLogout} tone="outline" />
    </Screen>
  );
}

const styles = StyleSheet.create({
  profileHero: {
    alignItems: 'center',
    paddingVertical: spacing.lg,
    gap: spacing.sm,
  },
  avatarLarge: {
    width: 76,
    height: 76,
    borderRadius: 38,
    backgroundColor: palette.brandDeep,
    alignItems: 'center',
    justifyContent: 'center',
    ...shadow.md,
  },
  avatarText: {
    color: '#ffffff',
    fontWeight: '900',
    fontSize: 26,
    letterSpacing: -0.5,
  },
  name: {
    fontSize: 20,
    fontWeight: '800',
    color: palette.text,
    letterSpacing: -0.3,
  },
  infoRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingVertical: spacing.sm,
    borderBottomWidth: 1,
    borderBottomColor: palette.line,
  },
  infoRowLast: {
    borderBottomWidth: 0,
  },
  infoKey: {
    color: palette.textLight,
    fontSize: 12,
    fontWeight: '700',
    textTransform: 'uppercase',
    letterSpacing: 0.6,
  },
  infoVal: {
    color: palette.text,
    fontSize: 13,
    fontWeight: '500',
    flexShrink: 1,
    textAlign: 'right',
  },
});