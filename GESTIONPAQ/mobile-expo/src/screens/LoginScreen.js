import React, { useState } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { Card, Field, Notice, Pill, PrimaryButton, Screen } from '../components/Ui';
import { palette, radius, spacing } from '../theme';

export function LoginScreen({ onLogin }) {
  const [login, setLogin] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [notice, setNotice] = useState({ type: 'info', message: '' });

  async function submit() {
    if (!login.trim() || !password) {
      setNotice({ type: 'error', message: 'Ingresa tu correo o usuario y la password.' });
      return;
    }

    setLoading(true);
    setNotice({ type: 'info', message: '' });

    try {
      await onLogin({ login: login.trim(), password });
    } catch (err) {
      setNotice({ type: 'error', message: err.message || 'No fue posible iniciar sesion.' });
    } finally {
      setLoading(false);
    }
  }

  return (
    <Screen eyebrow="Acceso seguro" title="Centro operativo movil" subtitle="La misma identidad visual del panel web, adaptada a uso en campo.">
      <Card tone="dark">
        <Pill tone="dark">Disponible por red local</Pill>
        <Text style={styles.logo}>GESTIONPAQ</Text>
        <Text style={styles.heroTitle}>Despacho, tracking y evidencia desde una sola consola.</Text>
        <Text style={styles.copy}>Acceso para perfiles operativos y clientes autorizados.</Text>
        <View style={styles.featureRow}>
          {['Rutas', 'Tracking', 'Evidencia'].map((item) => (
            <View key={item} style={styles.featureChip}>
              <Text style={styles.featureLabel}>{item}</Text>
            </View>
          ))}
        </View>
      </Card>
      <Card>
        <Field label="Correo o usuario" value={login} onChangeText={setLogin} placeholder="correo@gestionpaq.local" />
        <Field label="Password" value={password} onChangeText={setPassword} placeholder="********" secureTextEntry />
        <Notice message={notice.message} type={notice.type} />
        <PrimaryButton label={loading ? 'Ingresando...' : 'Entrar'} onPress={submit} disabled={loading} />
      </Card>
    </Screen>
  );
}

const styles = StyleSheet.create({
  logo: {
    color: palette.textOnDark,
    fontSize: 28,
    fontWeight: '800',
    letterSpacing: 0.3,
  },
  heroTitle: {
    color: palette.textOnDark,
    fontSize: 24,
    fontWeight: '800',
    lineHeight: 30,
  },
  copy: {
    color: palette.textMutedOnDark,
    lineHeight: 20,
  },
  featureRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
    marginTop: spacing.xs,
  },
  featureChip: {
    paddingHorizontal: 12,
    paddingVertical: 8,
    borderRadius: radius.pill,
    backgroundColor: 'rgba(255, 255, 255, 0.08)',
    borderWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.08)',
  },
  featureLabel: {
    color: palette.textOnDark,
    fontSize: 12,
    fontWeight: '700',
  },
});
