<template>
  <div class="wheel-settings">
    <header class="wheel-settings__header">
      <div>
        <p class="wheel-settings__eyebrow">Администрирование</p>
        <h1>Настройки колеса удачи</h1>
        <p class="wheel-settings__subtitle">Управляйте содержимым customWheelData через визуальный редактор</p>
      </div>
      <div class="wheel-settings__actions">
        <button class="btn ghost" type="button" @click="addSection">
          + Добавить сектор
        </button>
        <button class="btn primary" type="button" :disabled="isSaving || !sections.length" @click="saveSections">
          <span v-if="isSaving">Сохраняем…</span>
          <span v-else>Сохранить изменения</span>
        </button>
      </div>
    </header>

    <!-- Глобальные настройки -->
    <section class="wheel-settings__global" v-if="!isLoading">
      <div class="global-settings-card">
        <h2>Глобальные настройки</h2>
        <div class="global-settings__content">
          <label class="form-field checkbox-field">
            <input
              type="checkbox"
              v-model="wheelRequired"
            />
            <span>Использовать вероятностную логику (required = true)</span>
            <p class="field-hint">
              Если включено: колесо использует вероятности выпадения секторов.<br>
              Если выключено: колесо всегда останавливается на секторе с текстом "0".
            </p>
          </label>
          <div v-if="wheelRequired" class="probability-summary">
            <p :class="['probability-total', { 'probability-warning': Math.abs(getTotalProbability() - 100) > 0.01 }]">
              Сумма вероятностей: <strong>{{ getTotalProbability().toFixed(2) }}%</strong>
              <span v-if="Math.abs(getTotalProbability() - 100) > 0.01" class="probability-error">
                (должно быть 100%)
              </span>
            </p>
          </div>
        </div>
      </div>
    </section>

    <section class="wheel-settings__body" v-if="!isLoading">
      <div class="wheel-settings__editor">
        <article
          class="section-card"
          v-for="(section, index) in sections"
          :key="section.localId"
        >
          <div class="section-card__header">
            <div>
              <p class="section-card__eyebrow">Сектор №{{ index + 1 }}</p>
              <h3>{{ section.text || 'Без названия' }}</h3>
            </div>
            <div class="section-card__controls">
              <button
                class="icon-btn"
                type="button"
                :disabled="index === 0"
                @click="moveSection(index, -1)"
                title="Переместить вверх"
              >
                ↑
              </button>
              <button
                class="icon-btn"
                type="button"
                :disabled="index === sections.length - 1"
                @click="moveSection(index, 1)"
                title="Переместить вниз"
              >
                ↓
              </button>
              <button
                class="icon-btn danger"
                type="button"
                @click="removeSection(index)"
                title="Удалить"
              >
                ×
              </button>
            </div>
          </div>

          <div class="section-card__grid">
            <label class="form-field">
              <span>Текст (отображается на секторе)</span>
              <input
                type="text"
                v-model="section.text"
                placeholder="Например, 250"
              />
            </label>

            <label class="form-field">
              <span>Тип</span>
              <select v-model="section.type">
                <option
                  v-for="option in typeOptions"
                  :key="option.value"
                  :value="option.value"
                >
                  {{ option.label }}
                </option>
              </select>
            </label>

            <label class="form-field">
              <span>Ответ / описание</span>
              <textarea
                rows="3"
                v-model="section.answer"
                placeholder="Сообщение, которое увидит пользователь после выигрыша"
              ></textarea>
            </label>

            <label class="form-field" v-if="wheelRequired">
              <span>Вероятность выпадения (%)</span>
              <input
                type="number"
                v-model.number="section.probability"
                min="0"
                max="100"
                step="0.01"
                placeholder="0.00"
              />
              <p class="field-hint">
                Вероятность выпадения этого сектора в процентах (0-100).<br>
                Сумма всех вероятностей должна быть равна 100%.
              </p>
            </label>

            <div class="form-field">
              <span>Изображение (из Media Manager)</span>
              <btn-media
                v-model="section.image"
                :button-text="'Выбрать изображение'"
                button-class="btn btn-outline-primary"
                fields="url"
              />
              <div class="image-preview" v-if="section.image">
                <img :src="section.image" alt="Превью сектора" />
              </div>
            </div>
          </div>
        </article>

        <p class="empty-hint" v-if="!sections.length">
          Нет ни одного сектора. Добавьте новый, используя кнопку «Добавить сектор».
        </p>
      </div>

      <aside class="wheel-settings__preview">
        <h2>Предпросмотр</h2>
        <p class="preview-subtitle">Сектор обновляется в реальном времени</p>
        <div class="preview-grid">
          <div
            class="preview-item"
            v-for="(section, index) in sections"
            :key="`preview-${section.localId}`"
          >
            <div class="preview-item__badge">#{{ index + 1 }}</div>
            <div class="preview-item__image" v-if="section.image">
              <img :src="section.image" :alt="section.text" />
            </div>
            <div class="preview-item__content">
              <p class="preview-item__title">{{ section.text || 'Без названия' }}</p>
              <p class="preview-item__type">{{ typeLabel(section.type) }}</p>
              <p class="preview-item__answer" v-if="section.answer">{{ section.answer }}</p>
            </div>
          </div>
        </div>
      </aside>
    </section>

    <div class="wheel-settings__loader" v-else>
      <span>Загружаем данные колеса…</span>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

const createSection = (index = 0) => ({
  id: null,
  text: '',
  type: 'number',
  image: '',
  answer: '',
  position: index,
  is_active: true,
  probability: 0,
  localId: `${Date.now()}-${Math.random().toString(16).slice(2)}`
})

export default {
  name: 'WheelSettingsPage',
  data() {
    return {
      sections: [],
      isLoading: false,
      isSaving: false,
      wheelRequired: false,
      typeOptions: [
        { value: 'number', label: 'Число' },
        { value: 'gift', label: 'Подарок' },
        { value: 'answer', label: 'Ответ / текст' }
      ]
    }
  },
  mounted() {
    this.fetchSections()
  },
  methods: {
    async fetchSections() {
      this.isLoading = true
      try {
        const { data } = await axios.get('/api/v1/wheel-sections')
        const sections = data?.data ?? []
        this.wheelRequired = data?.settings?.required ?? false
        this.sections = (sections.length ? sections : [createSection(0)]).map((section, index) => ({
          id: section.id ?? null,
          text: section.text ?? '',
          type: section.type ?? 'number',
          image: section.image ?? '',
          answer: section.answer ?? '',
          position: section.position ?? index,
          is_active: section.is_active ?? true,
          probability: section.probability ?? 0,
          localId: `${section.id ?? 'new'}-${index}-${Math.random().toString(16).slice(2)}`
        }))
      } catch (error) {
        console.error(error)
        this.$notify?.({
          type: 'error',
          title: 'Ошибка загрузки',
          text: 'Не удалось получить данные колеса. Попробуйте обновить страницу.'
        })
        this.sections = [createSection(0)]
      } finally {
        this.isLoading = false
      }
    },
    addSection() {
      this.sections.push(createSection(this.sections.length))
    },
    removeSection(index) {
      this.sections.splice(index, 1)
    },
    moveSection(index, direction) {
      const targetIndex = index + direction
      if (targetIndex < 0 || targetIndex >= this.sections.length) return
      const clone = [...this.sections]
      const [moved] = clone.splice(index, 1)
      clone.splice(targetIndex, 0, moved)
      this.sections = clone
    },
    typeLabel(type) {
      return this.typeOptions.find(option => option.value === type)?.label || 'Неизвестно'
    },
    async saveSections() {
      this.isSaving = true
      try {
        // Проверяем сумму вероятностей, если включена вероятностная логика
        if (this.wheelRequired) {
          const totalProbability = this.sections.reduce((sum, s) => sum + (parseFloat(s.probability) || 0), 0)
          if (Math.abs(totalProbability - 100) > 0.01) {
            this.$notify?.({
              type: 'warning',
              title: 'Внимание',
              text: `Сумма вероятностей равна ${totalProbability.toFixed(2)}%, должна быть 100%. Автоматически нормализуем значения.`
            })
            // Нормализуем вероятности
            const normalizedTotal = totalProbability || 1
            this.sections.forEach(section => {
              section.probability = ((parseFloat(section.probability) || 0) / normalizedTotal * 100).toFixed(2)
            })
          }
        }

        const payload = this.sections.map((section, index) => ({
          id: section.id,
          text: section.text || `Сектор ${index + 1}`,
          type: section.type || 'number',
          image: section.image || null,
          answer: section.answer || null,
          position: index,
          is_active: true,
          probability: this.wheelRequired ? (parseFloat(section.probability) || 0) : 0
        }))

        const { data } = await axios.post('/api/v1/wheel-sections/sync', {
          sections: payload,
          settings: {
            required: this.wheelRequired
          }
        })
        const updated = data?.data ?? []
        this.wheelRequired = data?.settings?.required ?? false
        this.sections = updated.map((section, index) => ({
          ...section,
          image: section.image ?? '',
          answer: section.answer ?? '',
          probability: section.probability ?? 0,
          localId: `${section.id}-${index}-${Math.random().toString(16).slice(2)}`
        }))

        this.$notify?.({
          type: 'success',
          title: 'Сохранено',
          text: 'Колесо обновлено'
        })
      } catch (error) {
        console.error(error)
        const message = error.response?.data?.message || 'Не удалось сохранить настройки'
        this.$notify?.({
          type: 'error',
          title: 'Ошибка',
          text: message
        })
      } finally {
        this.isSaving = false
      }
    },
    getTotalProbability() {
      return this.sections.reduce((sum, s) => sum + (parseFloat(s.probability) || 0), 0)
    }
  }
}
</script>

<style scoped>
.wheel-settings {
  padding: 32px;
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.wheel-settings__header {
  display: flex;
  justify-content: space-between;
  gap: 24px;
  flex-wrap: wrap;
}

.wheel-settings__eyebrow {
  text-transform: uppercase;
  letter-spacing: 0.08em;
  font-size: 12px;
  color: #e77d65;
  margin-bottom: 4px;
}

.wheel-settings__subtitle {
  color: rgba(0, 0, 0, 0.6);
}

.wheel-settings__actions {
  display: flex;
  gap: 12px;
  align-items: flex-start;
  flex-wrap: wrap;
}

.btn {
  border: none;
  border-radius: 8px;
  padding: 10px 18px;
  font-weight: 600;
  cursor: pointer;
}

.btn.primary {
  background: linear-gradient(90deg, #f8a575 0%, #fdb083 100%);
  color: #fff;
}

.btn.primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn.ghost {
  background: rgba(0, 0, 0, 0.05);
  color: #333;
}

.wheel-settings__body {
  display: grid;
  grid-template-columns: minmax(0, 2fr) minmax(280px, 1fr);
  gap: 24px;
}

.wheel-settings__editor {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.section-card {
  border: 1px solid rgba(0, 0, 0, 0.08);
  border-radius: 16px;
  padding: 20px;
  background: #fff;
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.05);
}

.section-card__header {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  align-items: center;
  margin-bottom: 16px;
}

.section-card__eyebrow {
  text-transform: uppercase;
  font-size: 11px;
  color: rgba(0, 0, 0, 0.4);
  letter-spacing: 0.08em;
}

.section-card__controls {
  display: flex;
  gap: 8px;
}

.icon-btn {
  width: 34px;
  height: 34px;
  border-radius: 8px;
  border: 1px solid rgba(0, 0, 0, 0.1);
  background: #fff;
  cursor: pointer;
}

.icon-btn:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

.icon-btn.danger {
  color: #c0392b;
}

.section-card__grid {
  display: grid;
  gap: 16px;
}

.form-field {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-field span {
  font-size: 14px;
  color: rgba(0, 0, 0, 0.7);
}

.form-field input,
.form-field select,
.form-field textarea {
  border: 1px solid rgba(0, 0, 0, 0.15);
  border-radius: 10px;
  padding: 10px 12px;
  font-size: 14px;
  font-family: inherit;
}

.image-preview {
  margin-top: 8px;
  border: 1px dashed rgba(0, 0, 0, 0.1);
  border-radius: 12px;
  overflow: hidden;
  max-width: 220px;
}

.image-preview img {
  width: 100%;
  display: block;
}

.empty-hint {
  text-align: center;
  padding: 32px;
  color: rgba(0, 0, 0, 0.5);
}

.wheel-settings__preview {
  border: 1px solid rgba(0, 0, 0, 0.08);
  border-radius: 16px;
  padding: 20px;
  background: #fffdf9;
  box-shadow: 0 10px 40px rgba(255, 168, 120, 0.2);
}

.preview-subtitle {
  color: rgba(0, 0, 0, 0.6);
  margin-bottom: 12px;
}

.preview-grid {
  display: flex;
  flex-direction: column;
  gap: 12px;
  max-height: calc(100vh - 220px);
  overflow-y: auto;
}

.preview-item {
  display: flex;
  gap: 12px;
  padding: 12px;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.9);
  border: 1px solid rgba(0, 0, 0, 0.05);
}

.preview-item__badge {
  font-size: 12px;
  background: rgba(248, 165, 117, 0.2);
  color: #e77d65;
  padding: 4px 8px;
  border-radius: 999px;
  height: fit-content;
}

.preview-item__image {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  overflow: hidden;
  flex-shrink: 0;
}

.preview-item__image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.preview-item__title {
  font-weight: 600;
  margin-bottom: 2px;
}

.preview-item__type {
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: rgba(0, 0, 0, 0.5);
}

.preview-item__answer {
  font-size: 12px;
  color: rgba(0, 0, 0, 0.7);
  margin-top: 4px;
}

.wheel-settings__loader {
  padding: 60px;
  text-align: center;
  color: rgba(0, 0, 0, 0.6);
}

.wheel-settings__global {
  margin-bottom: 24px;
}

.global-settings-card {
  border: 1px solid rgba(0, 0, 0, 0.08);
  border-radius: 16px;
  padding: 20px;
  background: #fff;
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.05);
}

.global-settings-card h2 {
  margin: 0 0 16px 0;
  font-size: 18px;
  font-weight: 600;
}

.global-settings__content {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.checkbox-field {
  display: flex;
  flex-direction: row;
  align-items: flex-start;
  gap: 12px;
}

.checkbox-field input[type="checkbox"] {
  margin-top: 4px;
  width: 20px;
  height: 20px;
  cursor: pointer;
}

.checkbox-field span {
  flex: 1;
  font-weight: 500;
}

.field-hint {
  font-size: 12px;
  color: rgba(0, 0, 0, 0.5);
  margin-top: 4px;
  line-height: 1.4;
}

.probability-summary {
  margin-top: 12px;
  padding: 12px;
  background: rgba(248, 165, 117, 0.1);
  border-radius: 8px;
}

.probability-total {
  margin: 0;
  font-size: 14px;
  color: rgba(0, 0, 0, 0.7);
}

.probability-total.probability-warning {
  color: #c0392b;
}

.probability-error {
  color: #c0392b;
  font-weight: 600;
}

@media (max-width: 1100px) {
  .wheel-settings__body {
    grid-template-columns: 1fr;
  }

  .wheel-settings__preview {
    order: -1;
  }
}
</style>

