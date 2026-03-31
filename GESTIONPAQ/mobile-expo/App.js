import React, { useEffect, useState } from 'react';
import { ActivityIndicator, SafeAreaView, StatusBar, StyleSheet, View, Text, Pressable, useWindowDimensions } from 'react-native';
import { MaterialCommunityIcons } from '@expo/vector-icons';
import { StatusBar as ExpoStatusBar } from 'expo-status-bar';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { apiRequest, hydrateApiBase, STORAGE_KEYS } from './src/api';
import { LoginScreen } from './src/screens/LoginScreen';
import { HomeScreen } from './src/screens/HomeScreen';
import { ShipmentsScreen } from './src/screens/ShipmentsScreen';
import { RoutesScreen } from './src/screens/RoutesScreen';
import { TrackingScreen } from './src/screens/TrackingScreen';
import { EvidenceScreen } from './src/screens/EvidenceScreen';
import { ProfileScreen } from './src/screens/ProfileScreen';
import { getInitials, palette, radius, roleLabel, shadows, spacing } from './src/theme';

const TABS = [
  { key: 'home', label: 'Inicio', icon: 'view-dashboard-outline' },
  { key: 'shipments', label: 'Envios', icon: 'package-variant-closed' },
  { key: 'routes', label: 'Rutas', icon: 'map-marker-path' },
  { key: 'tracking', label: 'Rastreo', icon: 'crosshairs-gps' },
  { key: 'evidence', label: 'Prueba', icon: 'camera-outline' },
  { key: 'profile', label: 'Perfil', icon: 'account-circle-outline' },
];

function allowedTabs(role) {
  if (role === 'operator') {
    return TABS.filter((tab) => ['home', 'shipments', 'tracking', 'profile'].includes(tab.key));
  }

  if (role === 'supervisor') {
    return TABS.filter((tab) => ['home', 'shipments', 'routes', 'tracking', 'profile'].includes(tab.key));
  }

  if (role === 'dispatcher') {
    return TABS.filter((tab) => ['home', 'shipments', 'routes', 'tracking', 'profile'].includes(tab.key));
  }

  if (role === 'customer') {
    return TABS.filter((tab) => ['home', 'shipments', 'tracking', 'profile'].includes(tab.key));
  }

  if (role === 'driver') {
    return TABS.filter((tab) => ['home', 'routes', 'tracking', 'evidence', 'profile'].includes(tab.key));
  }

  return TABS;
}

function renderActiveScreen(activeTab, session, handleLogout) {
  if (activeTab === 'home') {
    return <HomeScreen token={session.token} user={session.user} />;
  }

  if (activeTab === 'shipments') {
    return <ShipmentsScreen token={session.token} user={session.user} />;
  }

  if (activeTab === 'routes') {
    return <RoutesScreen token={session.token} user={session.user} />;
  }

  if (activeTab === 'tracking') {
    return <TrackingScreen token={session.token} user={session.user} />;
  }

  if (activeTab === 'evidence') {
    return <EvidenceScreen token={session.token} user={session.user} />;
  }

  return <ProfileScreen user={session.user} onLogout={handleLogout} />;
}

export default function App() {
  const [session, setSession] = useState({ token: null, user: null, hydrated: false });
  const [activeTab, setActiveTab] = useState('home');
  const { width } = useWindowDimensions();

  useEffect(() => {
    (async () => {
      await hydrateApiBase();

      const [token, rawUser] = await Promise.all([
        AsyncStorage.getItem(STORAGE_KEYS.token),
        AsyncStorage.getItem(STORAGE_KEYS.user),
      ]);

      let user = null;

      if (rawUser) {
        try {
          user = JSON.parse(rawUser);
        } catch (error) {
          await AsyncStorage.multiRemove([STORAGE_KEYS.token, STORAGE_KEYS.user]);
        }
      }

      setSession({
        token,
        user,
        hydrated: true,
      });
    })();
  }, []);

  async function handleLogin(credentials) {
    const response = await apiRequest('/auth/login', {
      method: 'POST',
      data: credentials,
      skipAuth: true,
    });

    await AsyncStorage.multiSet([
      [STORAGE_KEYS.token, response.token],
      [STORAGE_KEYS.user, JSON.stringify(response.user)],
    ]);

    setSession({ token: response.token, user: response.user, hydrated: true });
    setActiveTab('home');
  }

  async function handleLogout() {
    try {
      await apiRequest('/auth/logout', { method: 'POST', token: session.token });
    } catch (error) {
      // Ignored: local logout should still succeed if token already expired.
    }

    await AsyncStorage.multiRemove([STORAGE_KEYS.token, STORAGE_KEYS.user]);
    setSession({ token: null, user: null, hydrated: true });
    setActiveTab('home');
  }

  if (!session.hydrated) {
    return (
      <SafeAreaView style={styles.appShell}>
        <View style={styles.orbTop} />
        <View style={styles.orbBottom} />
        <View style={styles.loadingPanel}>
          <ActivityIndicator color={palette.brand} />
          <Text style={styles.loadingEyebrow}>GESTIONPAQ Mobile</Text>
          <Text style={styles.loadingTitle}>Preparando centro operativo</Text>
          <Text style={styles.loadingText}>Sincronizando la sesion y resolviendo la API activa.</Text>
        </View>
      </SafeAreaView>
    );
  }

  if (!session.token || !session.user) {
    return (
      <SafeAreaView style={styles.appShell}>
        <View style={styles.orbTop} />
        <View style={styles.orbBottom} />
        <ExpoStatusBar style="dark" />
        <LoginScreen onLogin={handleLogin} />
      </SafeAreaView>
    );
  }

  const userTabs = allowedTabs(session.user.role);
  const displayRole = roleLabel(session.user.role);
  const compactHeader = width < 420;
  const tightHeader = width < 360;

  return (
    <SafeAreaView style={styles.appShell}>
      <View style={styles.orbTop} />
      <View style={styles.orbBottom} />
      <ExpoStatusBar style="dark" />
      <StatusBar barStyle="dark-content" />
      <View style={[styles.frame, tightHeader && styles.frameTight]}>
        <View style={[styles.header, compactHeader && styles.headerCompact]}>
          <View style={[styles.headerTop, compactHeader && styles.headerTopCompact]}>
            <View style={[styles.headerBrand, compactHeader && styles.headerBrandCompact]}>
              <View style={[styles.brandBadge, tightHeader && styles.brandBadgeTight]}>
                <MaterialCommunityIcons name="truck-fast-outline" size={tightHeader ? 24 : 28} color={palette.accent} />
              </View>
              <View style={styles.headerCopy}>
                <Text style={[styles.eyebrow, tightHeader && styles.eyebrowTight]}>Centro operativo movil</Text>
                <Text numberOfLines={1} style={[styles.title, compactHeader && styles.titleCompact]}>GESTIONPAQ</Text>
              </View>
            </View>

            <View style={[styles.accountChip, compactHeader && styles.accountChipCompact, tightHeader && styles.accountChipTight]}>
              <View style={[styles.avatar, tightHeader && styles.avatarTight]}>
                <Text style={[styles.avatarLabel, tightHeader && styles.avatarLabelTight]}>{getInitials(session.user.name)}</Text>
              </View>
              <View style={styles.accountMeta}>
                <Text numberOfLines={1} style={[styles.accountName, compactHeader && styles.accountNameCompact]}>
                  {session.user.name}
                </Text>
                <Text numberOfLines={1} style={[styles.accountRole, compactHeader && styles.accountRoleCompact]}>
                  {displayRole}
                </Text>
              </View>
            </View>
          </View>

          {!tightHeader ? (
            <Text numberOfLines={compactHeader ? 2 : 1} style={[styles.subtitle, compactHeader && styles.subtitleCompact]}>
              La identidad visual del panel web, llevada a rutas, tracking y prueba de entrega.
            </Text>
          ) : null}
        </View>

        <View style={styles.content}>
          {renderActiveScreen(activeTab, session, handleLogout)}
        </View>

        <View style={styles.tabBar}>
          {userTabs.map((tab) => {
            const active = activeTab === tab.key;

            return (
              <Pressable key={tab.key} style={[styles.tabButton, active && styles.tabButtonActive]} onPress={() => setActiveTab(tab.key)}>
                <View style={[styles.tabIconWrap, active && styles.tabIconWrapActive]}>
                  <MaterialCommunityIcons name={tab.icon} size={20} color={active ? palette.white : palette.textMutedOnDark} />
                </View>
                <Text style={[styles.tabLabel, active && styles.tabLabelActive]}>{tab.label}</Text>
              </Pressable>
            );
          })}
        </View>
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  appShell: {
    flex: 1,
    backgroundColor: palette.background,
  },
  orbTop: {
    position: 'absolute',
    width: 280,
    height: 280,
    borderRadius: 140,
    top: -110,
    left: -80,
    backgroundColor: 'rgba(255, 138, 61, 0.18)',
  },
  orbBottom: {
    position: 'absolute',
    width: 340,
    height: 340,
    borderRadius: 170,
    bottom: -150,
    right: -120,
    backgroundColor: 'rgba(15, 123, 108, 0.14)',
  },
  frame: {
    flex: 1,
    paddingHorizontal: spacing.md,
    paddingTop: spacing.sm,
    paddingBottom: spacing.md,
    gap: spacing.md,
  },
  frameTight: {
    paddingHorizontal: spacing.sm,
    gap: spacing.sm,
  },
  loadingPanel: {
    marginTop: '42%',
    marginHorizontal: spacing.lg,
    padding: spacing.xl,
    borderRadius: radius.xl,
    alignItems: 'center',
    gap: spacing.sm,
    backgroundColor: 'rgba(255, 253, 248, 0.78)',
    borderBottomWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.7)',
    borderWidth: 1,
    ...shadows.medium,
  },
  loadingEyebrow: {
    color: palette.brand,
    textTransform: 'uppercase',
    letterSpacing: 1.6,
    fontSize: 11,
    fontWeight: '700',
  },
  loadingTitle: {
    color: palette.text,
    fontSize: 24,
    fontWeight: '800',
  },
  loadingText: {
    color: palette.textMuted,
    fontSize: 15,
    textAlign: 'center',
    lineHeight: 22,
  },
  header: {
    gap: spacing.sm,
    padding: spacing.md,
    borderRadius: radius.xl,
    backgroundColor: palette.surfaceDark,
    borderWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.08)',
    ...shadows.floating,
  },
  headerCompact: {
    gap: spacing.xs,
    padding: spacing.sm + 2,
  },
  headerTop: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: spacing.sm,
  },
  headerTopCompact: {
    flexWrap: 'wrap',
    alignItems: 'flex-start',
  },
  headerBrand: {
    flex: 1,
    minWidth: 0,
    flexDirection: 'row',
    gap: spacing.md,
    alignItems: 'center',
  },
  headerBrandCompact: {
    gap: spacing.sm,
  },
  brandBadge: {
    width: 60,
    height: 60,
    borderRadius: 20,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: 'rgba(255, 138, 61, 0.16)',
  },
  brandBadgeTight: {
    width: 52,
    height: 52,
    borderRadius: 18,
  },
  headerCopy: {
    flex: 1,
    minWidth: 0,
    gap: 2,
  },
  eyebrow: {
    color: palette.accent,
    textTransform: 'uppercase',
    letterSpacing: 1.7,
    fontSize: 11,
    fontWeight: '700',
  },
  eyebrowTight: {
    fontSize: 10,
    letterSpacing: 1.3,
  },
  title: {
    marginTop: 1,
    fontSize: 26,
    fontWeight: '800',
    color: palette.textOnDark,
  },
  titleCompact: {
    fontSize: 22,
  },
  subtitle: {
    color: palette.textMutedOnDark,
    fontSize: 13,
    lineHeight: 18,
    marginLeft: 72,
    flexShrink: 1,
  },
  subtitleCompact: {
    marginLeft: 0,
    fontSize: 12,
    lineHeight: 17,
  },
  accountChip: {
    minWidth: 146,
    maxWidth: 180,
    paddingVertical: 8,
    paddingHorizontal: 10,
    borderRadius: radius.pill,
    backgroundColor: 'rgba(255, 255, 255, 0.08)',
    borderWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.08)',
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'flex-start',
    gap: spacing.sm,
  },
  accountChipCompact: {
    minWidth: 0,
    maxWidth: '100%',
    flexShrink: 1,
  },
  accountChipTight: {
    width: '100%',
  },
  avatar: {
    width: 42,
    height: 42,
    borderRadius: 14,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: palette.brand,
  },
  avatarTight: {
    width: 38,
    height: 38,
    borderRadius: 12,
  },
  avatarLabel: {
    color: palette.white,
    fontSize: 16,
    fontWeight: '800',
  },
  avatarLabelTight: {
    fontSize: 14,
  },
  accountMeta: {
    flex: 1,
    minWidth: 0,
    gap: 1,
  },
  accountName: {
    color: palette.textOnDark,
    fontWeight: '700',
    fontSize: 13,
  },
  accountNameCompact: {
    fontSize: 12.5,
  },
  accountRole: {
    color: palette.textMutedOnDark,
    fontSize: 12,
  },
  accountRoleCompact: {
    fontSize: 11.5,
  },
  content: {
    flex: 1,
  },
  tabBar: {
    flexDirection: 'row',
    gap: spacing.xs,
    padding: spacing.xs,
    borderRadius: radius.xl,
    backgroundColor: palette.surfaceDarkAlt,
    borderWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.08)',
    ...shadows.medium,
  },
  tabButton: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    gap: 5,
    paddingVertical: 10,
    paddingHorizontal: 4,
    borderRadius: radius.lg,
  },
  tabButtonActive: {
    backgroundColor: 'rgba(255, 255, 255, 0.07)',
    borderWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.08)',
  },
  tabIconWrap: {
    width: 34,
    height: 34,
    borderRadius: 14,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: 'rgba(255, 255, 255, 0.08)',
  },
  tabIconWrapActive: {
    backgroundColor: palette.brand,
  },
  tabLabel: {
    color: palette.textMutedOnDark,
    fontWeight: '700',
    fontSize: 11,
  },
  tabLabelActive: {
    color: palette.textOnDark,
  },
});