import React, { useEffect, useState } from 'react';
import { SafeAreaView, StatusBar, StyleSheet, View, Text, Pressable } from 'react-native';
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
import { palette, spacing, shadow, radius } from './src/theme';

const ROLE_LABELS = {
  admin: 'Administrador',
  operator: 'Operador',
  supervisor: 'Supervisor',
  dispatcher: 'Despachador',
  driver: 'Conductor',
  customer: 'Cliente',
};

const TABS = [
  { key: 'home',      label: 'Inicio',  icon: '⌂' },
  { key: 'shipments', label: 'Envíos',  icon: '↑' },
  { key: 'routes',    label: 'Rutas',   icon: '⊞' },
  { key: 'tracking',  label: 'Rastreo', icon: '◎' },
  { key: 'evidence',  label: 'Prueba',  icon: '◆' },
  { key: 'profile',   label: 'Perfil',  icon: '○' },
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

export default function App() {
  const [session, setSession] = useState({ token: null, user: null, hydrated: false });
  const [activeTab, setActiveTab] = useState('home');

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
      <SafeAreaView style={styles.loadingScreen}>
        <View style={styles.loadingLogoWrap}>
          <Text style={styles.loadingLogo}>G</Text>
        </View>
        <Text style={styles.loadingText}>Preparando GESTIONPAQ Mobile...</Text>
      </SafeAreaView>
    );
  }

  if (!session.token || !session.user) {
    return (
      <SafeAreaView style={styles.appShell}>
        <ExpoStatusBar style="light" />
        <LoginScreen onLogin={handleLogin} />
      </SafeAreaView>
    );
  }

  const userTabs = allowedTabs(session.user.role);
  const initials = (session.user.name || '?').split(' ').slice(0, 2).map((w) => w[0]).join('').toUpperCase();

  return (
    <SafeAreaView style={styles.appShell}>
      <ExpoStatusBar style="light" />
      <StatusBar barStyle="light-content" />
      <View style={styles.header}>
        <View style={styles.headerLeft}>
          <Text style={styles.eyebrow}>GESTIONPAQ</Text>
          <Text style={styles.title}>{session.user.name}</Text>
          <Text style={styles.subtitle}>{ROLE_LABELS[session.user.role] || session.user.role}</Text>
        </View>
        <View style={styles.avatar}>
          <Text style={styles.avatarText}>{initials}</Text>
        </View>
      </View>

      <View style={styles.content}>
        {activeTab === 'home' && <HomeScreen token={session.token} user={session.user} />}
        {activeTab === 'shipments' && <ShipmentsScreen token={session.token} user={session.user} />}
        {activeTab === 'routes' && <RoutesScreen token={session.token} user={session.user} />}
        {activeTab === 'tracking' && <TrackingScreen token={session.token} user={session.user} />}
        {activeTab === 'evidence' && <EvidenceScreen token={session.token} user={session.user} />}
        {activeTab === 'profile' && <ProfileScreen user={session.user} onLogout={handleLogout} />}
      </View>

      <View style={styles.tabBar}>
        {userTabs.map((tab) => {
          const active = activeTab === tab.key;

          return (
            <Pressable key={tab.key} style={styles.tabButton} onPress={() => setActiveTab(tab.key)}>
              <Text style={[styles.tabIcon, active && styles.tabIconActive]}>{tab.icon}</Text>
              <Text style={[styles.tabLabel, active && styles.tabLabelActive]}>{tab.label}</Text>
              {active && <View style={styles.tabActiveDot} />}
            </Pressable>
          );
        })}
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  appShell: {
    flex: 1,
    backgroundColor: palette.background,
  },
  loadingScreen: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: palette.brandDeep,
    gap: 16,
  },
  loadingLogoWrap: {
    width: 72,
    height: 72,
    borderRadius: 24,
    backgroundColor: 'rgba(255,255,255,0.15)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  loadingLogo: {
    fontSize: 38,
    fontWeight: '900',
    color: '#ffffff',
  },
  loadingText: {
    color: 'rgba(255,255,255,0.7)',
    fontSize: 14,
    fontWeight: '500',
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: spacing.lg,
    paddingTop: spacing.md,
    paddingBottom: spacing.md,
    backgroundColor: palette.brandDeep,
    ...shadow.md,
  },
  headerLeft: {
    gap: 2,
  },
  eyebrow: {
    color: 'rgba(255,255,255,0.65)',
    textTransform: 'uppercase',
    letterSpacing: 2,
    fontSize: 10,
    fontWeight: '700',
  },
  title: {
    fontSize: 19,
    fontWeight: '800',
    color: '#ffffff',
    letterSpacing: -0.2,
  },
  subtitle: {
    marginTop: 1,
    color: 'rgba(255,255,255,0.65)',
    fontSize: 12,
    fontWeight: '500',
  },
  avatar: {
    width: 44,
    height: 44,
    borderRadius: 22,
    backgroundColor: 'rgba(255,255,255,0.18)',
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1.5,
    borderColor: 'rgba(255,255,255,0.3)',
  },
  avatarText: {
    color: '#ffffff',
    fontWeight: '800',
    fontSize: 15,
  },
  content: {
    flex: 1,
  },
  tabBar: {
    flexDirection: 'row',
    paddingHorizontal: spacing.sm,
    paddingTop: spacing.sm,
    paddingBottom: spacing.md,
    backgroundColor: palette.surface,
    borderTopWidth: 1,
    borderTopColor: palette.line,
    ...shadow.lg,
  },
  tabButton: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 6,
    gap: 2,
  },
  tabIcon: {
    fontSize: 16,
    color: palette.textLight,
  },
  tabIconActive: {
    color: palette.brand,
  },
  tabLabel: {
    color: palette.textLight,
    fontWeight: '600',
    fontSize: 10,
    letterSpacing: 0.2,
  },
  tabLabelActive: {
    color: palette.brand,
    fontWeight: '700',
  },
  tabActiveDot: {
    width: 4,
    height: 4,
    borderRadius: 2,
    backgroundColor: palette.brand,
    marginTop: 1,
  },
});