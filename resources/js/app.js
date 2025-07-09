import './bootstrap';
import { createApp, ref } from 'vue';
import ExampleComponent from './components/ExampleComponent.vue';
import TurmaComponent from './components/TurmaComponent.vue';

const App = {
  components: { ExampleComponent, TurmaComponent },
  setup() {
    const pagina = ref('alunos');
    return { pagina };
  },
  template: `
    <div>
      <nav style="margin-bottom: 1em;">
        <button @click="pagina = 'alunos'" :disabled="pagina === 'alunos'">Alunos</button>
        <button @click="pagina = 'turmas'" :disabled="pagina === 'turmas'">Turmas</button>
      </nav>
      <example-component v-if="pagina === 'alunos'" />
      <turma-component v-else />
    </div>
  `
}

createApp(App).mount('#app');
