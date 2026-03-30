import React, { useState } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { Card, Field, Notice, PrimaryButton, Screen } from '../components/Ui';
import { palette } from '../theme';
import { getApiDebugInfo } from '../api';

export function LoginScreen({ onLogin }) {
  const [login, setLogin] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const apiDebug = getApiDebugInfo();
  const hintBase = apiDebug.activeApiBase || apiDebug.candidates[0] || 'sin base detectada';

  async function submit() {
    setLoading(true);
    setError('');

    try {
      await onLogin({ login: login.trim(), password });
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
        <Field label="Correo o usuario" value={login} onChangeText={setLogin} placeholder="correo@gestionpaq.local" />
        <Field label="Password" value={password} onChangeText={setPassword} placeholder="********" secureTextEntry />
        <Notice message={error} type="error" />
        <View style={styles.connectionBox}>
          <Text style={styles.connectionLabel}>Base API detectada</Text>
          <Text style={styles.connectionValue}>{hintBase}</Text>
        </View>
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
  connectionBox: {
    marginBottom: 14,
    paddingHorizontal: 12,
    paddingVertical: 10,
    borderRadius: 12,
    backgroundColor: palette.surfaceAlt,
  },
  connectionLabel: {
    color: palette.textMuted,
    fontSize: 12,
    fontWeight: '700',
    textTransform: 'uppercase',
    letterSpacing: 0.6,
  },
  connectionValue: {
    marginTop: 4,
    color: palette.text,
    fontSize: 12,
  },
});
