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
import { palette, spacing } from './src/theme';

const TABS = [
  { key: 'home', label: 'Inicio' },
  { key: 'shipments', label: 'Envios' },
  { key: 'routes', label: 'Rutas' },
  { key: 'tracking', label: 'Rastreo' },
  { key: 'evidence', label: 'Prueba' },
  { key: 'profile', label: 'Perfil' },
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
    return <SafeAreaView style={styles.loadingScreen}><Text style={styles.loadingText}>Preparando GESTIONPAQ Mobile...</Text></SafeAreaView>;
  }

  if (!session.token || !session.user) {
    return (
      <SafeAreaView style={styles.appShell}>
        <ExpoStatusBar style="dark" />
        <LoginScreen onLogin={handleLogin} />
      </SafeAreaView>
    );
  }

  const userTabs = allowedTabs(session.user.role);

  return (
    <SafeAreaView style={styles.appShell}>
      <ExpoStatusBar style="dark" />
      <StatusBar barStyle="dark-content" />
      <View style={styles.header}>
        <View>
          <Text style={styles.eyebrow}>GESTIONPAQ Mobile</Text>
          <Text style={styles.title}>{session.user.name}</Text>
          <Text style={styles.subtitle}>{session.user.role}</Text>
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
            <Pressable key={tab.key} style={[styles.tabButton, active && styles.tabButtonActive]} onPress={() => setActiveTab(tab.key)}>
              <Text style={[styles.tabLabel, active && styles.tabLabelActive]}>{tab.label}</Text>
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
    backgroundColor: palette.background,
  },
  loadingText: {
    color: palette.textMuted,
    fontSize: 16,
  },
  header: {
    paddingHorizontal: spacing.lg,
    paddingTop: spacing.md,
    paddingBottom: spacing.md,
    backgroundColor: palette.surface,
    borderBottomWidth: 1,
    borderBottomColor: palette.line,
  },
  eyebrow: {
    color: palette.brand,
    textTransform: 'uppercase',
    letterSpacing: 1.8,
    fontSize: 11,
    fontWeight: '700',
  },
  title: {
    marginTop: 6,
    fontSize: 22,
    fontWeight: '800',
    color: palette.text,
  },
  subtitle: {
    marginTop: 2,
    color: palette.textMuted,
  },
  content: {
    flex: 1,
  },
  tabBar: {
    flexDirection: 'row',
    gap: 8,
    paddingHorizontal: spacing.md,
    paddingTop: spacing.sm,
    paddingBottom: spacing.lg,
    backgroundColor: palette.surface,
    borderTopWidth: 1,
    borderTopColor: palette.line,
  },
  tabButton: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 12,
    borderRadius: 14,
    backgroundColor: palette.surfaceAlt,
  },
  tabButtonActive: {
    backgroundColor: palette.brand,
  },
  tabLabel: {
    color: palette.textMuted,
    fontWeight: '700',
    fontSize: 12,
  },
  tabLabelActive: {
    color: palette.surface,
  },
});