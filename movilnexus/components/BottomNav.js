import { View, Text, TouchableOpacity, StyleSheet } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { Colors } from '../constants/theme';

const tabs = [
  { id: 'home',     label: 'Inicio',         iconActivo: 'home',              iconInactivo: 'home-outline'              },
  { id: 'reservar', label: 'Reservar',        iconActivo: 'calendar',          iconInactivo: 'calendar-outline'          },
  { id: 'notifs',   label: 'Notificaciones',  iconActivo: 'notifications',     iconInactivo: 'notifications-outline'     },
  { id: 'perfil',   label: 'Perfil',          iconActivo: 'person',            iconInactivo: 'person-outline'            },
];

export default function BottomNav({ pantalla, onNavegar }) {
  return (
    <View style={styles.container}>
      {tabs.map(tab => {
        const activo = pantalla === tab.id;
        return (
          <TouchableOpacity
            key={tab.id}
            style={styles.tab}
            onPress={() => onNavegar(tab.id)}
          >
            {activo && <View style={styles.indicador}/>}
            <Ionicons
              name={activo ? tab.iconActivo : tab.iconInactivo}
              size={24}
              color={activo ? Colors.cyan : Colors.textMuted}
            />
            <Text style={[styles.label, activo && styles.labelActivo]}>
              {tab.label}
            </Text>
          </TouchableOpacity>
        );
      })}
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flexDirection:   'row',
    backgroundColor: Colors.bgCard,
    borderTopWidth:  1,
    borderTopColor:  'rgba(255,255,255,0.06)',
    height:          75,
    paddingBottom:   10,
    paddingTop:      8,
  },
  tab: {
    flex:           1,
    alignItems:     'center',
    justifyContent: 'center',
    gap:            4,
    position:       'relative',
  },
  indicador: {
    position:        'absolute',
    top:             0,
    width:           28,
    height:          3,
    borderRadius:    2,
    backgroundColor: Colors.cyan,
  },
  label: {
    fontSize:   10,
    fontWeight: '700',
    color:      Colors.textMuted,
  },
  labelActivo: {
    color: Colors.cyan,
  },
});