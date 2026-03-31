import React from 'react';
import { ActivityIndicator, Pressable, ScrollView, StyleSheet, Text, TextInput, View } from 'react-native';
import { palette, spacing, radius, shadow } from '../theme';

const PILL_STYLES = {
  brand:   { bg: palette.brandLight,   color: palette.brandDeep },
  soft:    { bg: palette.accentLight,  color: '#d97706' },
  success: { bg: palette.successLight, color: '#047857' },
  danger:  { bg: palette.dangerLight,  color: '#dc2626' },
  warning: { bg: palette.warningLight, color: '#c2410c' },
  info:    { bg: palette.infoLight,    color: '#1d4ed8' },
  neutral: { bg: '#f1f5f9',            color: '#475569' },
};

export function Screen({ title, subtitle, children, scroll = true }) {
  const Wrapper = scroll ? ScrollView : View;

  return (
    <Wrapper contentContainerStyle={styles.screenContent} style={styles.screen}>
      {(title || subtitle) && (
        <View style={styles.screenHeader}>
          {title ? <Text style={styles.screenTitle}>{title}</Text> : null}
          {subtitle ? <Text style={styles.screenSubtitle}>{subtitle}</Text> : null}
        </View>
      )}
      {children}
    </Wrapper>
  );
}

export function Card({ children, accent = false }) {
  return (
    <View style={[styles.card, shadow.sm, accent && styles.cardAccent]}>
      {children}
    </View>
  );
}

export function StatCard({ label, value, detail }) {
  return (
    <View style={[styles.statCard, shadow.md]}>
      <View style={styles.statAccentBar} />
      <View style={styles.statBody}>
        <Text style={styles.statLabel}>{label}</Text>
        <Text style={styles.statValue}>{value}</Text>
        {detail ? <Text style={styles.statDetail}>{detail}</Text> : null}
      </View>
    </View>
  );
}

export function Pill({ children, tone = 'brand' }) {
  const t = PILL_STYLES[tone] || PILL_STYLES.brand;
  return (
    <Text style={[styles.pill, { backgroundColor: t.bg, color: t.color }]}>
      {children}
    </Text>
  );
}

export function PrimaryButton({ label, onPress, tone = 'brand', disabled = false }) {
  return (
    <Pressable
      onPress={onPress}
      disabled={disabled}
      style={({ pressed }) => [
        styles.button,
        tone === 'outline' ? styles.buttonOutline : styles.buttonBrand,
        disabled && styles.buttonDisabled,
        pressed && styles.buttonPressed,
      ]}
    >
      <Text style={[styles.buttonLabel, tone === 'outline' ? styles.buttonLabelOutline : styles.buttonLabelBrand]}>
        {label}
      </Text>
    </Pressable>
  );
}

export function Field({ label, value, onChangeText, secureTextEntry = false, placeholder = '', autoCapitalize = 'none' }) {
  return (
    <View style={styles.field}>
      <Text style={styles.fieldLabel}>{label}</Text>
      <TextInput
        value={value}
        onChangeText={onChangeText}
        placeholder={placeholder}
        placeholderTextColor={palette.textLight}
        style={styles.input}
        secureTextEntry={secureTextEntry}
        autoCapitalize={autoCapitalize}
      />
    </View>
  );
}

export function Notice({ message, type = 'info' }) {
  if (!message) {
    return null;
  }

  const isError = type === 'error';
  return (
    <View style={[styles.notice, isError ? styles.noticeError : styles.noticeInfo]}>
      <Text style={[styles.noticeText, isError ? styles.noticeTextError : styles.noticeTextInfo]}>
        {isError ? '⚠ ' : 'ℹ '}{message}
      </Text>
    </View>
  );
}

export function LoadingState({ label = 'Cargando...' }) {
  return (
    <View style={styles.loadingWrap}>
      <View style={styles.loadingSpinnerWrap}>
        <ActivityIndicator color={palette.brand} size="large" />
      </View>
      <Text style={styles.loadingLabel}>{label}</Text>
    </View>
  );
}

export function EmptyState({ title, subtitle }) {
  return (
    <View style={[styles.emptyWrap, shadow.sm]}>
      <Text style={styles.emptyIcon}>◎</Text>
      <Text style={styles.emptyTitle}>{title}</Text>
      {subtitle ? <Text style={styles.emptySubtitle}>{subtitle}</Text> : null}
    </View>
  );
}

const styles = StyleSheet.create({
  screen: {
    flex: 1,
    backgroundColor: palette.background,
  },
  screenContent: {
    padding: spacing.md,
    paddingBottom: spacing.xl,
    gap: spacing.md,
  },
  screenHeader: {
    gap: 4,
    paddingBottom: spacing.xs,
  },
  screenTitle: {
    fontSize: 22,
    fontWeight: '800',
    color: palette.text,
    letterSpacing: -0.3,
  },
  screenSubtitle: {
    color: palette.textMuted,
    fontSize: 13,
    lineHeight: 18,
  },
  card: {
    borderRadius: radius.lg,
    padding: spacing.md,
    backgroundColor: palette.surface,
    borderWidth: 1,
    borderColor: palette.line,
    gap: 10,
  },
  cardAccent: {
    backgroundColor: palette.surfaceAlt,
    borderColor: 'rgba(15, 123, 108, 0.15)',
  },
  statCard: {
    borderRadius: radius.lg,
    backgroundColor: palette.surface,
    borderWidth: 1,
    borderColor: palette.line,
    flexDirection: 'row',
    overflow: 'hidden',
  },
  statAccentBar: {
    width: 5,
    backgroundColor: palette.brand,
    borderTopLeftRadius: radius.lg,
    borderBottomLeftRadius: radius.lg,
  },
  statBody: {
    flex: 1,
    padding: spacing.md,
    gap: 4,
  },
  statLabel: {
    fontSize: 10,
    textTransform: 'uppercase',
    letterSpacing: 1.4,
    color: palette.textMuted,
    fontWeight: '700',
  },
  statValue: {
    fontSize: 30,
    fontWeight: '800',
    color: palette.brandDeep,
    letterSpacing: -1,
  },
  statDetail: {
    color: palette.textMuted,
    fontSize: 13,
  },
  pill: {
    alignSelf: 'flex-start',
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: radius.full,
    fontSize: 11,
    fontWeight: '700',
    overflow: 'hidden',
    letterSpacing: 0.3,
  },
  button: {
    borderRadius: radius.md,
    paddingVertical: 15,
    alignItems: 'center',
    justifyContent: 'center',
  },
  buttonBrand: {
    backgroundColor: palette.brand,
    ...shadow.sm,
  },
  buttonOutline: {
    backgroundColor: palette.surface,
    borderWidth: 1.5,
    borderColor: palette.lineDark,
  },
  buttonDisabled: {
    opacity: 0.45,
  },
  buttonPressed: {
    opacity: 0.8,
  },
  buttonLabel: {
    fontSize: 15,
    fontWeight: '700',
    letterSpacing: 0.2,
  },
  buttonLabelBrand: {
    color: '#ffffff',
  },
  buttonLabelOutline: {
    color: palette.text,
  },
  field: {
    gap: 6,
  },
  fieldLabel: {
    color: palette.text,
    fontWeight: '600',
    fontSize: 13,
    letterSpacing: 0.2,
  },
  input: {
    paddingHorizontal: 14,
    paddingVertical: 13,
    borderRadius: radius.md,
    backgroundColor: palette.surfaceDim,
    borderWidth: 1.5,
    borderColor: palette.line,
    color: palette.text,
    fontSize: 15,
  },
  notice: {
    borderRadius: radius.md,
    paddingHorizontal: 14,
    paddingVertical: 12,
  },
  noticeInfo: {
    backgroundColor: palette.infoLight,
    borderLeftWidth: 3,
    borderLeftColor: palette.info,
  },
  noticeError: {
    backgroundColor: palette.dangerLight,
    borderLeftWidth: 3,
    borderLeftColor: palette.danger,
  },
  noticeText: {
    fontSize: 13,
    lineHeight: 18,
    fontWeight: '500',
  },
  noticeTextInfo: {
    color: '#1e40af',
  },
  noticeTextError: {
    color: '#991b1b',
  },
  loadingWrap: {
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: spacing.xl * 2,
    gap: 14,
  },
  loadingSpinnerWrap: {
    width: 60,
    height: 60,
    borderRadius: 30,
    backgroundColor: palette.brandLight,
    alignItems: 'center',
    justifyContent: 'center',
  },
  loadingLabel: {
    color: palette.textMuted,
    fontSize: 14,
    fontWeight: '500',
  },
  emptyWrap: {
    borderRadius: radius.lg,
    padding: spacing.lg,
    backgroundColor: palette.surface,
    borderWidth: 1,
    borderColor: palette.line,
    alignItems: 'center',
    gap: 10,
  },
  emptyIcon: {
    fontSize: 32,
    color: palette.textLight,
  },
  emptyTitle: {
    fontSize: 16,
    fontWeight: '700',
    color: palette.text,
    textAlign: 'center',
  },
  emptySubtitle: {
    color: palette.textMuted,
    lineHeight: 20,
    fontSize: 13,
    textAlign: 'center',
  },
});