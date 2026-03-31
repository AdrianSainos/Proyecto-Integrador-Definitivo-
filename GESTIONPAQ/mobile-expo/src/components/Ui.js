import React from 'react';
import { ActivityIndicator, Pressable, ScrollView, StyleSheet, Text, TextInput, View } from 'react-native';
import { palette, radius, shadows, spacing } from '../theme';

export function Screen({ title, subtitle, eyebrow = 'Operacion movil', children, scroll = true, contentContainerStyle }) {
  if (!scroll) {
    return (
      <View style={styles.screen}>
        {(title || subtitle || eyebrow) && (
          <View style={styles.screenHeader}>
            {eyebrow ? <Text style={styles.screenEyebrow}>{eyebrow}</Text> : null}
            {title ? <Text style={styles.screenTitle}>{title}</Text> : null}
            {subtitle ? <Text style={styles.screenSubtitle}>{subtitle}</Text> : null}
          </View>
        )}
        <View style={[styles.staticContent, contentContainerStyle]}>{children}</View>
      </View>
    );
  }

  return (
    <ScrollView
      contentContainerStyle={[styles.screenContent, contentContainerStyle]}
      style={styles.screen}
      showsVerticalScrollIndicator={false}
      keyboardShouldPersistTaps="handled"
    >
      {(title || subtitle || eyebrow) && (
        <View style={styles.screenHeader}>
          {eyebrow ? <Text style={styles.screenEyebrow}>{eyebrow}</Text> : null}
          {title ? <Text style={styles.screenTitle}>{title}</Text> : null}
          {subtitle ? <Text style={styles.screenSubtitle}>{subtitle}</Text> : null}
        </View>
      )}
      {children}
    </ScrollView>
  );
}

export function Card({ children, tone = 'default', style }) {
  return (
    <View
      style={[
        styles.card,
        tone === 'soft' && styles.cardSoft,
        tone === 'dark' && styles.cardDark,
        tone === 'brand' && styles.cardBrand,
        style,
      ]}
    >
      {children}
    </View>
  );
}

export function StatCard({ label, value, detail, tone = 'brand', style }) {
  return (
    <View
      style={[
        styles.statCard,
        tone === 'accent' && styles.statCardAccent,
        tone === 'info' && styles.statCardInfo,
        tone === 'success' && styles.statCardSuccess,
        tone === 'neutral' && styles.statCardNeutral,
        style,
      ]}
    >
      <View style={styles.statHeader}>
        <Text style={styles.statLabel}>{label}</Text>
        <View
          style={[
            styles.statDot,
            tone === 'accent' && styles.statDotAccent,
            tone === 'info' && styles.statDotInfo,
            tone === 'success' && styles.statDotSuccess,
            tone === 'neutral' && styles.statDotNeutral,
          ]}
        />
      </View>
      <Text style={styles.statValue}>{value}</Text>
      {detail ? <Text style={styles.statDetail}>{detail}</Text> : null}
    </View>
  );
}

export function Pill({ children, tone = 'brand', style }) {
  return (
    <Text
      style={[
        styles.pill,
        tone === 'soft' && styles.pillSoft,
        tone === 'accent' && styles.pillAccent,
        tone === 'info' && styles.pillInfo,
        tone === 'success' && styles.pillSuccess,
        tone === 'danger' && styles.pillDanger,
        tone === 'neutral' && styles.pillNeutral,
        tone === 'dark' && styles.pillDark,
        style,
      ]}
    >
      {children}
    </Text>
  );
}

export function PrimaryButton({ label, onPress, tone = 'brand', disabled = false, style }) {
  return (
    <Pressable
      onPress={onPress}
      disabled={disabled}
      style={[
        styles.button,
        tone === 'outline' && styles.buttonOutline,
        tone === 'soft' && styles.buttonSoft,
        tone === 'danger' && styles.buttonDanger,
        tone === 'brand' && styles.buttonBrand,
        disabled && styles.buttonDisabled,
        style,
      ]}
    >
      <Text
        style={[
          styles.buttonLabel,
          tone === 'outline' && styles.buttonLabelOutline,
          tone === 'soft' && styles.buttonLabelSoft,
          tone === 'danger' && styles.buttonLabelBrand,
          tone === 'brand' && styles.buttonLabelBrand,
        ]}
      >
        {label}
      </Text>
    </Pressable>
  );
}

export function Field({
  label,
  value,
  onChangeText,
  secureTextEntry = false,
  placeholder = '',
  autoCapitalize = 'none',
  multiline = false,
}) {
  return (
    <View style={styles.field}>
      <Text style={styles.fieldLabel}>{label}</Text>
      <TextInput
        value={value}
        onChangeText={onChangeText}
        placeholder={placeholder}
        placeholderTextColor={palette.textSoft}
        style={[styles.input, multiline && styles.inputMultiline]}
        secureTextEntry={secureTextEntry}
        autoCapitalize={autoCapitalize}
        multiline={multiline}
        textAlignVertical={multiline ? 'top' : 'center'}
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
      <View style={[styles.noticeBar, type === 'error' ? styles.noticeBarError : styles.noticeBarInfo]} />
      <Text style={styles.noticeText}>{message}</Text>
    </View>
  );
}

export function LoadingState({ label = 'Cargando...' }) {
  return (
    <View style={styles.loadingWrap}>
      <View style={styles.loadingCard}>
        <ActivityIndicator color={palette.brand} />
        <Text style={styles.loadingLabel}>{label}</Text>
      </View>
    </View>
  );
}

export function EmptyState({ title, subtitle }) {
  return (
    <Card style={styles.emptyCard}>
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
    paddingHorizontal: spacing.md,
    paddingTop: spacing.md,
    paddingBottom: spacing.xxl,
    gap: spacing.md,
  },
  staticContent: {
    flex: 1,
    paddingHorizontal: spacing.md,
    paddingBottom: spacing.xl,
    gap: spacing.md,
  },
  screenHeader: {
    gap: spacing.xs,
    padding: spacing.lg,
    borderRadius: radius.xl,
    borderWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.68)',
    backgroundColor: 'rgba(255, 253, 248, 0.72)',
    ...shadows.soft,
  },
  screenEyebrow: {
    color: palette.brand,
    textTransform: 'uppercase',
    letterSpacing: 1.6,
    fontSize: 11,
    fontWeight: '700',
  },
  screenTitle: {
    fontSize: 28,
    fontWeight: '800',
    color: palette.text,
  },
  screenSubtitle: {
    color: '#3f4d62',
    lineHeight: 20,
  },
  card: {
    borderRadius: radius.lg,
    padding: spacing.lg,
    backgroundColor: palette.surface,
    borderWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.7)',
    gap: spacing.sm,
    ...shadows.soft,
  },
  cardSoft: {
    backgroundColor: 'rgba(244, 248, 247, 0.92)',
  },
  cardBrand: {
    backgroundColor: 'rgba(15, 123, 108, 0.12)',
  },
  cardDark: {
    backgroundColor: palette.surfaceDark,
    borderColor: 'rgba(255, 255, 255, 0.08)',
    ...shadows.floating,
  },
  statCard: {
    flex: 1,
    minWidth: 145,
    borderRadius: radius.lg,
    padding: spacing.md,
    backgroundColor: 'rgba(255, 255, 255, 0.86)',
    borderWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.76)',
    gap: spacing.xs,
    ...shadows.soft,
  },
  statCardAccent: {
    backgroundColor: 'rgba(255, 138, 61, 0.12)',
  },
  statCardInfo: {
    backgroundColor: 'rgba(46, 125, 184, 0.12)',
  },
  statCardSuccess: {
    backgroundColor: 'rgba(46, 154, 99, 0.12)',
  },
  statCardNeutral: {
    backgroundColor: 'rgba(123, 135, 152, 0.12)',
  },
  statHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  statLabel: {
    fontSize: 11,
    textTransform: 'uppercase',
    letterSpacing: 1.2,
    color: palette.textMuted,
    fontWeight: '700',
  },
  statDot: {
    width: 10,
    height: 10,
    borderRadius: radius.pill,
    backgroundColor: palette.brand,
  },
  statDotAccent: {
    backgroundColor: palette.accent,
  },
  statDotInfo: {
    backgroundColor: palette.info,
  },
  statDotSuccess: {
    backgroundColor: palette.success,
  },
  statDotNeutral: {
    backgroundColor: palette.neutral,
  },
  statValue: {
    fontSize: 30,
    fontWeight: '800',
    color: palette.text,
  },
  statDetail: {
    color: palette.textMuted,
    lineHeight: 18,
  },
  pill: {
    alignSelf: 'flex-start',
    paddingHorizontal: 12,
    paddingVertical: 7,
    borderRadius: radius.pill,
    fontWeight: '700',
    overflow: 'hidden',
    fontSize: 12,
  },
  pillBrand: {
    backgroundColor: 'rgba(15, 123, 108, 0.12)',
    color: palette.brand,
  },
  pillSoft: {
    backgroundColor: 'rgba(255, 255, 255, 0.62)',
    color: palette.text,
  },
  pillAccent: {
    backgroundColor: 'rgba(255, 138, 61, 0.14)',
    color: palette.accent,
  },
  pillInfo: {
    backgroundColor: 'rgba(46, 125, 184, 0.14)',
    color: palette.info,
  },
  pillSuccess: {
    backgroundColor: 'rgba(46, 154, 99, 0.14)',
    color: palette.success,
  },
  pillDanger: {
    backgroundColor: 'rgba(207, 76, 76, 0.14)',
    color: palette.danger,
  },
  pillNeutral: {
    backgroundColor: 'rgba(123, 135, 152, 0.14)',
    color: palette.textMuted,
  },
  pillDark: {
    backgroundColor: 'rgba(255, 255, 255, 0.08)',
    color: palette.textOnDark,
  },
  button: {
    minHeight: 52,
    borderRadius: radius.md,
    paddingVertical: 14,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: spacing.md,
  },
  buttonBrand: {
    backgroundColor: palette.brand,
    ...shadows.medium,
  },
  buttonOutline: {
    backgroundColor: 'rgba(255, 255, 255, 0.68)',
    borderWidth: 1,
    borderColor: palette.line,
  },
  buttonSoft: {
    backgroundColor: 'rgba(255, 138, 61, 0.14)',
    borderWidth: 1,
    borderColor: 'rgba(255, 138, 61, 0.18)',
  },
  buttonDanger: {
    backgroundColor: palette.danger,
    ...shadows.medium,
  },
  buttonDisabled: {
    opacity: 0.5,
  },
  buttonLabel: {
    fontSize: 15,
    fontWeight: '800',
  },
  buttonLabelBrand: {
    color: palette.white,
  },
  buttonLabelOutline: {
    color: palette.text,
  },
  buttonLabelSoft: {
    color: palette.accentDeep,
  },
  field: {
    gap: spacing.xs,
  },
  fieldLabel: {
    color: palette.text,
    fontWeight: '700',
  },
  input: {
    paddingHorizontal: 14,
    paddingVertical: 14,
    borderRadius: radius.md,
    backgroundColor: 'rgba(255, 255, 255, 0.78)',
    borderWidth: 1,
    borderColor: 'rgba(26, 38, 62, 0.08)',
    color: palette.text,
    minHeight: 52,
  },
  inputMultiline: {
    minHeight: 112,
    paddingTop: 14,
  },
  notice: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.sm,
    borderRadius: radius.md,
    padding: 14,
    borderWidth: 1,
  },
  noticeInfo: {
    borderColor: 'rgba(46, 125, 184, 0.14)',
    backgroundColor: 'rgba(23, 162, 184, 0.12)',
  },
  noticeError: {
    borderColor: 'rgba(207, 76, 76, 0.16)',
    backgroundColor: 'rgba(207, 76, 76, 0.12)',
  },
  noticeBar: {
    width: 4,
    borderRadius: radius.pill,
    alignSelf: 'stretch',
  },
  noticeBarInfo: {
    backgroundColor: palette.info,
  },
  noticeBarError: {
    backgroundColor: palette.danger,
  },
  noticeText: {
    color: palette.text,
    flex: 1,
    lineHeight: 20,
  },
  loadingWrap: {
    paddingHorizontal: spacing.md,
    paddingTop: spacing.xl,
  },
  loadingCard: {
    borderRadius: radius.lg,
    paddingVertical: spacing.xl,
    alignItems: 'center',
    justifyContent: 'center',
    gap: spacing.sm,
    backgroundColor: 'rgba(255, 253, 248, 0.74)',
    borderWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.68)',
    ...shadows.soft,
  },
  loadingLabel: {
    color: palette.textMuted,
  },
  emptyCard: {
    backgroundColor: 'rgba(255, 253, 248, 0.72)',
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