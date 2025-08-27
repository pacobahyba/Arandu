import { defineStore } from 'pinia'
import axios from 'axios'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    token: sessionStorage.getItem('token') || null,
  }),
  actions: {
    setToken(token) {
      this.token = token
      sessionStorage.setItem('token', token)
      // configura axios para enviar Authorization automaticamente
      axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
    },
    logout() {
      this.token = null
      sessionStorage.removeItem('token')
      delete axios.defaults.headers.common['Authorization']
    }
  }
})