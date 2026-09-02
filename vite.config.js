import { defineConfig } from 'vite'

export default defineConfig({
  build: {
    outDir: 'js',
    emptyOutDir: true,
    rollupOptions: {
      input: { 'team-folders-main': 'src/main.js', 'team-folders-admin': 'src/admin.js' },
      output: { entryFileNames: '[name].js' },
    },
  },
})
