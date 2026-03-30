import React, { useState } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { Card, Field, Notice, PrimaryButton, Screen } from '../components/Ui';
import { palette } from '../theme';

export function LoginScreen({ onLogin }) {
  const [email, setEmail] = useState('driver@gestionpaq.local');
  const [password, setPassword] = useState('driver123');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  async function submit() {
    setLoading(true);
    setError('');

    try {
      await onLogin({ email: email.trim(), password });
    } catch (err) {
      setError(err.message || 'No fue posible iniciar sesion.');
    } finally {
      setLoading(false);
    }
  }

  return (
    <Screen title="Acceso movil" subtitle="Ingresa con tu cuenta operativa.">
      <Card accent>
        <Text style={styles.logo}>GESTIONPAQ</Text>
        <Text style={styles.copy}>Acceso para perfiles operativos y clientes autorizados.</Text>
      </Card>
      <Card>
        <Field label="Correo" value={email} onChangeText={setEmail} placeholder="correo@gestionpaq.local" />
        <Field label="Password" value={password} onChangeText={setPassword} placeholder="********" secureTextEntry />
        <Notice message={error} type="error" />
        <PrimaryButton label={loading ? 'Ingresando...' : 'Entrar'} onPress={submit} disabled={loading} />
      </Card>
    </Screen>
  );
}

const styles = StyleSheet.create({
  logo: {
    color: palette.brandDeep,
    fontSize: 26,
    fontWeight: '800',
  },
  copy: {
    color: palette.textMuted,
    lineHeight: 20,
  },
});
