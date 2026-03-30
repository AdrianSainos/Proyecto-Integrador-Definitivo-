import React from 'react';
import { ActivityIndicator, Pressable, ScrollView, StyleSheet, Text, TextInput, View } from 'react-native';
import { palette, spacing } from '../theme';

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
  return <View style={[styles.card, accent && styles.cardAccent]}>{children}</View>;
}

export function StatCard({ label, value, detail }) {
  return (
    <Card accent>
      <Text style={styles.statLabel}>{label}</Text>
      <Text style={styles.statValue}>{value}</Text>
      <Text style={styles.statDetail}>{detail}</Text>
    </Card>
  );
}

export function Pill({ children, tone = 'brand' }) {
  return <Text style={[styles.pill, tone === 'soft' ? styles.pillSoft : styles.pillBrand]}>{children}</Text>;
}

export function PrimaryButton({ label, onPress, tone = 'brand', disabled = false }) {
  return (
    <Pressable onPress={onPress} disabled={disabled} style={[styles.button, tone === 'outline' ? styles.buttonOutline : styles.buttonBrand, disabled && styles.buttonDisabled]}>
      <Text style={[styles.buttonLabel, tone === 'outline' ? styles.buttonLabelOutline : styles.buttonLabelBrand]}>{label}</Text>
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
        placeholderTextColor="#8a97a8"
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

  return (
    <View style={[styles.notice, type === 'error' ? styles.noticeError : styles.noticeInfo]}>
      <Text style={styles.noticeText}>{message}</Text>
    </View>
  );
}

export function LoadingState({ label = 'Cargando...' }) {
  return (
    <View style={styles.loadingWrap}>
      <ActivityIndicator color={palette.brand} />
      <Text style={styles.loadingLabel}>{label}</Text>
    </View>
  );
}

export function EmptyState({ title, subtitle }) {
  return (
    <Card>
      <Text style={styles.emptyTitle}>{title}</Text>
      <Text style={styles.emptySubtitle}>{subtitle}</Text>
    </Card>
  );
}

const styles = StyleSheet.create({
  screen: {
    flex: 1,
  },
  screenContent: {
    padding: spacing.md,
    gap: spacing.md,
  },
  screenHeader: {
    gap: 6,
  },
  screenTitle: {
    fontSize: 24,
    fontWeight: '800',
    color: palette.text,
  },
  screenSubtitle: {
    color: palette.textMuted,
    lineHeight: 20,
  },
  card: {
    borderRadius: 20,
    padding: spacing.md,
    backgroundColor: palette.surface,
    borderWidth: 1,
    borderColor: palette.line,
    gap: 8,
  },
  cardAccent: {
    backgroundColor: '#f7fbfa',
  },
  statLabel: {
    fontSize: 11,
    textTransform: 'uppercase',
    letterSpacing: 1.6,
    color: palette.textMuted,
    fontWeight: '700',
  },
  statValue: {
    fontSize: 28,
    fontWeight: '800',
    color: palette.brandDeep,
  },
  statDetail: {
    color: palette.textMuted,
  },
  pill: {
    alignSelf: 'flex-start',
    paddingHorizontal: 12,
    paddingVertical: 7,
    borderRadius: 999,
    fontWeight: '700',
    overflow: 'hidden',
  },
  pillBrand: {
    backgroundColor: 'rgba(15, 123, 108, 0.12)',
    color: palette.brand,
  },
  pillSoft: {
    backgroundColor: 'rgba(255, 138, 61, 0.14)',
    color: palette.accent,
  },
  button: {
    borderRadius: 16,
    paddingVertical: 14,
    alignItems: 'center',
    justifyContent: 'center',
  },
  buttonBrand: {
    backgroundColor: palette.brand,
  },
  buttonOutline: {
    backgroundColor: palette.surface,
    borderWidth: 1,
    borderColor: palette.line,
  },
  buttonDisabled: {
    opacity: 0.5,
  },
  buttonLabel: {
    fontSize: 15,
    fontWeight: '800',
  },
  buttonLabelBrand: {
    color: palette.surface,
  },
  buttonLabelOutline: {
    color: palette.text,
  },
  field: {
    gap: 8,
  },
  fieldLabel: {
    color: palette.text,
    fontWeight: '700',
  },
  input: {
    paddingHorizontal: 14,
    paddingVertical: 14,
    borderRadius: 14,
    backgroundColor: palette.surface,
    borderWidth: 1,
    borderColor: palette.line,
    color: palette.text,
  },
  notice: {
    borderRadius: 16,
    padding: 14,
  },
  noticeInfo: {
    backgroundColor: 'rgba(23, 162, 184, 0.12)',
  },
  noticeError: {
    backgroundColor: 'rgba(207, 76, 76, 0.12)',
  },
  noticeText: {
    color: palette.text,
  },
  loadingWrap: {
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: spacing.xl,
    gap: 10,
  },
  loadingLabel: {
    color: palette.textMuted,
  },
  emptyTitle: {
    fontSize: 18,
    fontWeight: '800',
    color: palette.text,
  },
  emptySubtitle: {
    color: palette.textMuted,
    lineHeight: 20,
  },
});