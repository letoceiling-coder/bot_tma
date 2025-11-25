<template>
  <div class="channel-settings-container">
    <h1>Управление каналами подписки</h1>

    <div class="info-box">
      <p><strong>Важно:</strong> Убедитесь что бот добавлен как администратор во все каналы!</p>
      <p>Для проверки подписки используйте <code>telegram_chat_id</code> (например: @channel_username или -1001234567890)</p>
    </div>

    <div class="channels-list">
      <div
        v-for="(channel, index) in channels"
        :key="channel.id || `new-${index}`"
        class="channel-card"
      >
        <div class="channel-header">
          <h3>Канал {{ index + 1 }}</h3>
          <button @click="removeChannel(index)" class="btn btn-danger btn-sm">
            Удалить
          </button>
        </div>
        
        <div class="form-group">
          <label>Название:</label>
          <input type="text" v-model="channel.title" class="form-control" placeholder="Основной канал" />
        </div>

        <div class="form-group">
          <label>Описание:</label>
          <textarea v-model="channel.description" class="form-control" rows="2" placeholder="Новости и обновления"></textarea>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Username (без @):</label>
            <input type="text" v-model="channel.username" class="form-control" placeholder="channel_username" />
          </div>
          <div class="form-group">
            <label>URL:</label>
            <input type="text" v-model="channel.url" class="form-control" placeholder="https://t.me/channel_username" />
          </div>
        </div>

        <div class="form-group">
          <label>Telegram Chat ID (для проверки):</label>
          <input 
            type="text" 
            v-model="channel.telegram_chat_id" 
            class="form-control" 
            placeholder="@channel_username или -1001234567890"
          />
          <small class="form-text text-muted">
            Если не указано, будет использован username с @. Для приватных каналов укажите chat_id (например: -1001234567890)
          </small>
        </div>

        <div class="form-group">
          <label>Изображение (аватар):</label>
          <btn-media
            v-model="channel.avatar"
            :button-text="'Выбрать изображение'"
            button-class="btn btn-outline-primary"
            fields="url"
          />
          <div v-if="channel.avatar" class="image-preview mt-2">
            <img :src="channel.avatar" alt="Preview" style="max-width: 100px; max-height: 100px;" />
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Порядок сортировки:</label>
            <input type="number" v-model.number="channel.sort_order" class="form-control" min="0" />
          </div>
          <div class="form-group">
            <label class="checkbox-label">
              <input type="checkbox" v-model="channel.is_active" />
              Активен
            </label>
          </div>
          <div class="form-group">
            <label class="checkbox-label">
              <input type="checkbox" v-model="channel.is_required" />
              Обязателен
            </label>
          </div>
        </div>
      </div>
    </div>

    <div class="actions">
      <button @click="addChannel" class="btn btn-success">
        + Добавить канал
      </button>
      <button @click="saveChannels" class="btn btn-primary" :disabled="saving">
        {{ saving ? 'Сохранение...' : 'Сохранить все каналы' }}
      </button>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import BtnMedia from '/resources/js/src/UI/Media/components/btn-media.vue'

export default {
  name: 'ChannelSettings',
  components: {
    BtnMedia
  },
  data() {
    return {
      channels: [],
      saving: false
    }
  },
  mounted() {
    this.loadChannels()
  },
  methods: {
    async loadChannels() {
      try {
        const { data } = await axios.get('/api/v1/channels')
        this.channels = data.data.map(ch => ({
          id: ch.id,
          title: ch.title || '',
          description: ch.description || '',
          username: ch.username || '',
          url: ch.url || '',
          avatar: ch.avatar || null,
          telegram_chat_id: ch.telegram_chat_id || null,
          sort_order: ch.sort_order ?? 0,
          is_active: ch.is_active !== undefined ? ch.is_active : true,
          is_required: ch.is_required !== undefined ? ch.is_required : true
        }))
        
        // Если каналов нет, показываем пустую форму
        if (this.channels.length === 0) {
          this.addChannel()
        }
      } catch (error) {
        console.error('Ошибка при загрузке каналов:', error)
        this.$notify({
          title: 'Ошибка',
          text: 'Не удалось загрузить каналы',
          type: 'error'
        })
        // Добавляем пустой канал для заполнения
        this.addChannel()
      }
    },
    
    addChannel() {
      this.channels.push({
        title: '',
        description: '',
        username: '',
        url: '',
        avatar: null,
        telegram_chat_id: null,
        sort_order: this.channels.length,
        is_active: true,
        is_required: true
      })
    },
    
    async removeChannel(index) {
      if (!confirm('Удалить этот канал?')) {
        return
      }

      const channel = this.channels[index]
      
      // Если канал уже сохранен в БД (есть ID), удаляем через API
      if (channel.id) {
        try {
          await axios.delete(`/api/v1/channels/${channel.id}`)
          
          this.$notify({
            title: 'Успех',
            text: 'Канал удален',
            type: 'success'
          })
        } catch (error) {
          console.error('Ошибка при удалении канала:', error)
          this.$notify({
            title: 'Ошибка',
            text: error.response?.data?.message || 'Не удалось удалить канал',
            type: 'error'
          })
          return // Не удаляем из массива если ошибка
        }
      }
      
      // Удаляем канал из локального массива
      this.channels.splice(index, 1)
    },
    
    async saveChannels() {
      // Валидация только если есть каналы
      if (this.channels.length > 0) {
        for (let i = 0; i < this.channels.length; i++) {
          const ch = this.channels[i]
          if (!ch.title || !ch.username || !ch.url) {
            this.$notify({
              title: 'Ошибка валидации',
              text: `Канал ${i + 1}: заполните название, username и URL`,
              type: 'error'
            })
            return
          }
        }
      }

      this.saving = true
      try {
        // Фильтруем и формируем payload только для заполненных каналов
        const payload = this.channels
          .filter(channel => channel.title && channel.username && channel.url)
          .map((channel, index) => ({
            id: channel.id,
            title: channel.title,
            description: channel.description || '',
            username: channel.username.replace('@', ''), // Убираем @ если есть
            url: channel.url,
            avatar: channel.avatar || null,
            telegram_chat_id: channel.telegram_chat_id || null,
            sort_order: channel.sort_order ?? index,
            is_active: channel.is_active !== false,
            is_required: channel.is_required !== false
          }))

        const { data } = await axios.post('/api/v1/channels/sync', { channels: payload })
        
        // Обновляем список каналов из ответа
        if (data.data && Array.isArray(data.data)) {
          this.channels = data.data.map(ch => ({
            id: ch.id,
            title: ch.title,
            description: ch.description,
            username: ch.username,
            url: ch.url,
            avatar: ch.avatar,
            telegram_chat_id: ch.telegram_chat_id,
            sort_order: ch.sort_order,
            is_active: ch.is_active,
            is_required: ch.is_required
          }))
        } else {
          this.channels = []
        }

        this.$notify({
          title: 'Успех',
          text: data.message || 'Каналы сохранены',
          type: 'success'
        })
      } catch (error) {
        console.error('Ошибка при сохранении каналов:', error)
        
        // Формируем детальное сообщение об ошибке
        let errorMessage = 'Не удалось сохранить каналы'
        if (error.response?.data) {
          if (error.response.data.message) {
            errorMessage = error.response.data.message
          }
          if (error.response.data.errors) {
            const errors = Object.values(error.response.data.errors).flat()
            errorMessage += ': ' + errors.join(', ')
          }
        }
        
        this.$notify({
          title: 'Ошибка',
          text: errorMessage,
          type: 'error'
        })
      } finally {
        this.saving = false
      }
    }
  }
}
</script>

<style scoped>
.channel-settings-container {
  padding: 20px;
  max-width: 1000px;
  margin: 0 auto;
}

h1 {
  margin-bottom: 20px;
  color: #333;
}

.info-box {
  background: #fff3cd;
  border: 1px solid #ffc107;
  border-radius: 8px;
  padding: 15px;
  margin-bottom: 20px;
}

.info-box p {
  margin: 5px 0;
}

.info-box code {
  background: #f8f9fa;
  padding: 2px 6px;
  border-radius: 3px;
  font-size: 0.9em;
}

.channels-list {
  margin-bottom: 20px;
}

.channel-card {
  background: #f9f9f9;
  border: 1px solid #ddd;
  border-radius: 8px;
  padding: 20px;
  margin-bottom: 15px;
}

.channel-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
  padding-bottom: 10px;
  border-bottom: 1px solid #ddd;
}

.channel-header h3 {
  margin: 0;
  color: #333;
}

.form-group {
  margin-bottom: 15px;
}

.form-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 15px;
  margin-bottom: 15px;
}

.form-group label {
  display: block;
  margin-bottom: 5px;
  font-weight: 500;
  color: #555;
}

.form-group input[type="text"],
.form-group input[type="number"],
.form-group textarea {
  width: 100%;
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
}

.form-group textarea {
  resize: vertical;
  min-height: 60px;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
}

.checkbox-label input[type="checkbox"] {
  width: auto;
  cursor: pointer;
}

.form-text {
  display: block;
  margin-top: 5px;
  font-size: 12px;
  color: #666;
}

.image-preview img {
  border: 1px solid #eee;
  border-radius: 4px;
  padding: 5px;
  background: #fff;
}

.actions {
  display: flex;
  gap: 10px;
  padding-top: 20px;
  border-top: 2px solid #eee;
}

.btn {
  padding: 10px 20px;
  border: none;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-success {
  background: #28a745;
  color: white;
}

.btn-success:hover {
  background: #218838;
}

.btn-primary {
  background: #007bff;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: #0056b3;
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-danger {
  background: #dc3545;
  color: white;
  padding: 6px 12px;
  font-size: 12px;
}

.btn-danger:hover {
  background: #c82333;
}

.btn-outline-primary {
  background: white;
  color: #007bff;
  border: 1px solid #007bff;
}

.btn-outline-primary:hover {
  background: #007bff;
  color: white;
}
</style>

