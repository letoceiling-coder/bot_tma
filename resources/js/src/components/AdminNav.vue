<template>
  <nav class="admin-nav">
    <div class="admin-nav__container">
      <div class="admin-nav__header">
        <h2 class="admin-nav__title">Администрирование</h2>
      </div>
      <ul class="admin-nav__list">
        <li class="admin-nav__item">
          <router-link 
            to="/admin/media" 
            class="admin-nav__link"
            :class="{ 'admin-nav__link--active': $route.path === '/admin/media' }"
          >
            <i class="fa-solid fa-images admin-nav__icon"></i>
            <span class="admin-nav__text">Медиа-менеджер</span>
          </router-link>
        </li>
        <li class="admin-nav__item">
          <router-link 
            to="/admin/settings/wheel" 
            class="admin-nav__link"
            :class="{ 'admin-nav__link--active': $route.path === '/admin/settings/wheel' }"
          >
            <i class="fa-solid fa-circle-notch admin-nav__icon"></i>
            <span class="admin-nav__text">Настройки колеса</span>
          </router-link>
        </li>
        <li class="admin-nav__item">
          <router-link 
            to="/admin/settings/channels" 
            class="admin-nav__link"
            :class="{ 'admin-nav__link--active': $route.path === '/admin/settings/channels' }"
          >
            <i class="fa-solid fa-bullhorn admin-nav__icon"></i>
            <span class="admin-nav__text">Управление каналами</span>
          </router-link>
        </li>
      </ul>
    </div>
  </nav>
</template>

<script>
export default {
  name: 'AdminNav'
}
</script>

<style scoped>
.admin-nav {
  background: #fff;
  border-bottom: 1px solid #e0e0e0;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
  margin-bottom: 20px;
}

.admin-nav__container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 20px;
}

.admin-nav__header {
  padding: 15px 0 10px;
  border-bottom: 1px solid #f0f0f0;
  margin-bottom: 10px;
}

.admin-nav__title {
  font-size: 14px;
  font-weight: 600;
  color: #666;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin: 0;
}

.admin-nav__list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  gap: 5px;
  flex-wrap: wrap;
}

.admin-nav__item {
  margin: 0;
}

.admin-nav__link {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  color: #555;
  text-decoration: none;
  border-radius: 6px;
  transition: all 0.2s ease;
  font-size: 14px;
  font-weight: 500;
  border: 1px solid transparent;
}

.admin-nav__link:hover {
  background: #f5f5f5;
  color: #333;
  text-decoration: none;
}

.admin-nav__link--active {
  background: #007bff;
  color: #fff;
  border-color: #007bff;
}

.admin-nav__link--active:hover {
  background: #0056b3;
  border-color: #0056b3;
  color: #fff;
}

.admin-nav__icon {
  font-size: 16px;
  width: 20px;
  text-align: center;
}

.admin-nav__text {
  white-space: nowrap;
}

@media (max-width: 768px) {
  .admin-nav__container {
    padding: 0 15px;
  }
  
  .admin-nav__list {
    flex-direction: column;
    gap: 0;
  }
  
  .admin-nav__link {
    border-radius: 0;
    border-left: 3px solid transparent;
    border-right: none;
    border-top: none;
    border-bottom: 1px solid #f0f0f0;
  }
  
  .admin-nav__link--active {
    border-left-color: #007bff;
    background: #f0f7ff;
    color: #007bff;
  }
  
  .admin-nav__link--active:hover {
    background: #e0efff;
    color: #0056b3;
  }
}
</style>

