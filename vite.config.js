import { defineConfig } from 'vite'

export default defineConfig({
  build: {
    outDir: 'js',
    emptyOutDir: true,
    lib: { entry: 'src/main.js', formats: ['es'], fileName: () => 'team-folders-main.js' },
  },
})
