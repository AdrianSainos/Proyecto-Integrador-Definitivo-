import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { Card, Pill, PrimaryButton, Screen } from '../components/Ui';
import { getInitials, palette, roleLabel, spacing } from '../theme';

export function ProfileScreen({ user, onLogout }) {
  return (
    <Screen eyebrow="Cuenta activa" title="Perfil" subtitle="Sesion activa y salida segura.">
      <Card tone="dark">
        <View style={styles.heroRow}>
          <View style={styles.avatar}>
            <Text style={styles.avatarLabel}>{getInitials(user.name)}</Text>
          </View>
          <View style={styles.heroCopy}>
            <Pill tone="dark">{roleLabel(user.role)}</Pill>
            <Text style={styles.name}>{user.name}</Text>
            <Text style={styles.metaDark}>{user.email}</Text>
          </View>
        </View>
      </Card>

      <Card>
        <Text style={styles.sectionTitle}>Contexto operativo</Text>
        <View style={styles.detailGrid}>
          <View style={styles.detailTile}>
            <Text style={styles.detailLabel}>Usuario</Text>
            <Text style={styles.detailValue}>{user.username ? `@${user.username}` : 'No disponible'}</Text>
          </View>
          <View style={styles.detailTile}>
            <Text style={styles.detailLabel}>Rol</Text>
            <Text style={styles.detailValue}>{roleLabel(user.role)}</Text>
          </View>
          <View style={styles.detailTile}>
            <Text style={styles.detailLabel}>Puesto</Text>
            <Text style={styles.detailValue}>{user.jobTitle || 'Sin puesto asignado'}</Text>
          </View>
          <View style={styles.detailTile}>
            <Text style={styles.detailLabel}>Horario</Text>
            <Text style={styles.detailValue}>{user.schedule || 'Sin horario base'}</Text>
          </View>
        </View>
      </Card>

      <PrimaryButton label="Cerrar sesion" onPress={onLogout} tone="outline" />
    </Screen>
  );
}

const styles = StyleSheet.create({
  heroRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  avatar: {
    width: 62,
    height: 62,
    borderRadius: 22,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: 'rgba(255, 255, 255, 0.08)',
  },
  avatarLabel: {
    color: palette.textOnDark,
    fontSize: 22,
    fontWeight: '800',
  },
  heroCopy: {
    flex: 1,
    gap: spacing.xs,
  },
  name: {
    fontSize: 22,
    fontWeight: '800',
    color: palette.textOnDark,
  },
  metaDark: {
    color: palette.textMutedOnDark,
    lineHeight: 20,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: '800',
    color: palette.text,
  },
  detailGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
  },
  detailTile: {
    flexGrow: 1,
    minWidth: '47%',
    padding: spacing.sm,
    borderRadius: 16,
    backgroundColor: 'rgba(255, 255, 255, 0.62)',
    borderWidth: 1,
    borderColor: 'rgba(26, 38, 62, 0.06)',
  },
  detailLabel: {
    color: palette.textMuted,
    fontSize: 11,
    textTransform: 'uppercase',
    letterSpacing: 0.8,
    fontWeight: '700',
  },
  detailValue: {
    marginTop: 6,
    color: palette.text,
    fontWeight: '700',
  },
  meta: {
    color: palette.textMuted,
    lineHeight: 20,
  },
});