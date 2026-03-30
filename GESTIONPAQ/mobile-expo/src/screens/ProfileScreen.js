import React from 'react';
import { StyleSheet, Text } from 'react-native';
import { Card, Pill, PrimaryButton, Screen } from '../components/Ui';
import { palette } from '../theme';

export function ProfileScreen({ user, onLogout }) {
  return (
    <Screen title="Perfil" subtitle="Sesion activa y salida segura.">
      <Card accent>
        <Pill>{user.role}</Pill>
        <Text style={styles.name}>{user.name}</Text>
        <Text style={styles.meta}>{user.username ? `@${user.username}` : ''}</Text>
        <Text style={styles.meta}>{user.email}</Text>
        <Text style={styles.meta}>{user.jobTitle || 'Sin puesto asignado'}</Text>
        <Text style={styles.meta}>{user.schedule || 'Sin horario base'}</Text>
      </Card>

      <PrimaryButton label="Cerrar sesion" onPress={onLogout} tone="outline" />
    </Screen>
  );
}

const styles = StyleSheet.create({
  name: {
    fontSize: 22,
    fontWeight: '800',
    color: palette.text,
  },
  meta: {
    color: palette.textMuted,
    lineHeight: 20,
  },
});