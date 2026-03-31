import React, { useState } from 'react';
import { KeyboardAvoidingView, Platform, ScrollView, StyleSheet, Text, View } from 'react-native';
import { Field, Notice, PrimaryButton } from '../components/Ui';
import { palette, radius, shadow, spacing } from '../theme';
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
      setError(err.message || 'No fue posible iniciar sesión.');
    } finally {
      setLoading(false);
    }
  }

  return (
    <KeyboardAvoidingView style={styles.root} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
      <View style={styles.hero}>
        <View style={styles.logoCircle}>
          <Text style={styles.logoLetter}>G</Text>
        </View>
        <Text style={styles.brandName}>GESTIONPAQ</Text>
        <Text style={styles.brandTagline}>Plataforma logística móvil</Text>
      </View>

      <View style={styles.sheet}>
        <ScrollView contentContainerStyle={styles.sheetContent} keyboardShouldPersistTaps="handled">
          <Text style={styles.sheetTitle}>Iniciar sesión</Text>
          <Text style={styles.sheetSubtitle}>Accede con tu cuenta operativa o de cliente.</Text>

          <View style={styles.form}>
            <Field label="Correo o usuario" value={login} onChangeText={setLogin} placeholder="correo@gestionpaq.local" />
            <Field label="Contraseña" value={password} onChangeText={setPassword} placeholder="••••••••" secureTextEntry />
            <Notice message={error} type="error" />
            <PrimaryButton label={loading ? 'Ingresando...' : 'Entrar'} onPress={submit} disabled={loading} />
          </View>

          <View style={styles.connectionBox}>
            <Text style={styles.connectionLabel}>API detectada</Text>
            <Text style={styles.connectionValue} numberOfLines={1}>{hintBase}</Text>
          </View>
        </ScrollView>
      </View>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  root: {
    flex: 1,
    backgroundColor: palette.brandDeep,
  },
  hero: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    gap: 12,
    paddingTop: spacing.xl,
  },
  logoCircle: {
    width: 80,
    height: 80,
    borderRadius: 26,
    backgroundColor: 'rgba(255,255,255,0.15)',
    borderWidth: 1.5,
    borderColor: 'rgba(255,255,255,0.3)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  logoLetter: {
    fontSize: 44,
    fontWeight: '900',
    color: '#ffffff',
  },
  brandName: {
    color: '#ffffff',
    fontSize: 22,
    fontWeight: '900',
    letterSpacing: 3,
  },
  brandTagline: {
    color: 'rgba(255,255,255,0.6)',
    fontSize: 13,
    fontWeight: '400',
    letterSpacing: 0.3,
  },
  sheet: {
    backgroundColor: palette.background,
    borderTopLeftRadius: 28,
    borderTopRightRadius: 28,
    ...shadow.lg,
  },
  sheetContent: {
    padding: spacing.lg,
    paddingBottom: spacing.xl,
    gap: spacing.md,
  },
  sheetTitle: {
    fontSize: 22,
    fontWeight: '800',
    color: palette.text,
    letterSpacing: -0.3,
  },
  sheetSubtitle: {
    color: palette.textMuted,
    fontSize: 13,
    marginTop: -spacing.sm,
    marginBottom: spacing.xs,
  },
  form: {
    gap: spacing.md,
  },
  connectionBox: {
    marginTop: spacing.xs,
    paddingHorizontal: 14,
    paddingVertical: 10,
    borderRadius: radius.md,
    backgroundColor: palette.surface,
    borderWidth: 1,
    borderColor: palette.line,
    gap: 3,
  },
  connectionLabel: {
    color: palette.textLight,
    fontSize: 10,
    fontWeight: '700',
    textTransform: 'uppercase',
    letterSpacing: 1,
  },
  connectionValue: {
    color: palette.textMuted,
    fontSize: 11,
  },
});
