const { getDefaultConfig } = require('expo/metro-config');
const path = require('path');

const config = getDefaultConfig(__dirname);

// Reducir carpetas vigiladas — excluir vendor de Laravel y storage
config.watchFolders = [__dirname];

config.resolver.blockList = [
  // Excluir el backend Laravel completo para no vigilar 400MB extra
  /.*\/GESTIONPAQ\/vendor\/.*/,
  /.*\/GESTIONPAQ\/storage\/.*/,
  /.*\/GESTIONPAQ\/bootstrap\/cache\/.*/,
  /.*\/GESTIONPAQ\/public\/.*/,
  /.*\/GESTIONPAQ\/database\/sql\/.*/,
];

// Aumentar workers para bundling más rápido
config.maxWorkers = 4;

module.exports = config;
