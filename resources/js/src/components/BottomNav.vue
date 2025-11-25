<template>
  <nav class="bottom-nav">
    <button
      v-for="item in items"
      :key="item.id"
      type="button"
      class="nav-item"
      :class="{ active: activeTab === item.id }"
      @click="$emit('change', item.id)"
    >
      <span class="nav-icon" :class="item.icon"></span>
      <span class="nav-text">{{ item.label }}</span>
    </button>
  </nav>
</template>

<script>
export default {
  name: 'BottomNav',
  props: {
    activeTab: {
      type: String,
      default: 'wheel'
    },
    items: {
      type: Array,
      default: () => [
        { id: 'wheel', label: 'Колесо', icon: 'wheel-icon' },
        { id: 'friends', label: 'Друзья', icon: 'friends-icon' },
        { id: 'top', label: 'Топ', icon: 'top-icon' }
      ]
    }
  },
  emits: ['change']
}
</script>

<style scoped>
.bottom-nav {
  position: fixed;
  left: 50%;
  bottom: 12px;
  transform: translateX(-50%);
  width: calc(100% - 32px);
  max-width: 343px;
  height: 58px;
  margin: 0 auto;
  background: #e77d65;
  backdrop-filter: blur(10px);
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: space-around;
  padding: 0 20px;
  z-index: 10;
}

.nav-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  cursor: pointer;
  flex: 1;
  background: transparent;
  border: none;
  padding: 0;
}

.nav-icon {
  width: 24px;
  height: 24px;
  border: 2px solid rgba(255, 255, 255, 0.7);
  border-radius: 4px;
  background: rgba(255, 255, 255, 0.2);
  transition: border-color 0.2s ease, background 0.2s ease;
}

.nav-item.active .nav-icon {
  border-color: #ffd489;
  background: #ffd489;
}

.nav-item.active .nav-text {
  color: #ffd489;
}

.nav-text {
  font-size: 10px;
  font-weight: 500;
  line-height: 12px;
  color: #ffffff;
  transition: color 0.2s ease;
}

.wheel-icon,
.friends-icon,
.top-icon {
  color: inherit;
}

@media (max-width: 375px) {
  .bottom-nav {
    width: calc(100% - 24px);
  }
}
</style>

