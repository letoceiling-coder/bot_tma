<template>
  <div class="pages-wrapper">
    <button class="close-btn" @click="goToLoad" aria-label="Закрыть">
      ✕
    </button>
    <component
      :is="currentSlide.component"
      v-bind="currentSlide.props"
      class="page-slide"
    />

    <PageCta
      :message="currentSlide.props.message"
      :dots="slides.length"
      :active-dot="currentIndex"
      :disable-prev="isFirst"
      :disable-next="isLast"
      @prev="showPrev"
      @next="showNext"
    />
  </div>
</template>

<script>
import { markRaw } from 'vue'
import PageIntro from '/resources/js/src/Pages/Page/index.vue'
import PageTwo from '/resources/js/src/Pages/Page2/index.vue'
import PageThree from '/resources/js/src/Pages/Page3/index.vue'
import PageCta from '/resources/js/src/components/PageCta.vue'

export default {
  name: 'PagesWrapper',
  components: {
    PageCta
  },
  data() {
    return {
      currentIndex: 0,
      slides: [
        {
          component: markRaw(PageIntro),
          props: { message: 'Подключаем игру…' }
        },
        {
          component: markRaw(PageTwo),
          props: { message: 'Приглашай друзей и получай билеты' }
        },
        {
          component: markRaw(PageThree),
          props: { message: 'Веди друзей в топ и забирай призы' }
        }
      ]
    }
  },
  computed: {
    currentSlide() {
      return this.slides[this.currentIndex]
    },
    isFirst() {
      return this.currentIndex === 0
    },
    isLast() {
      return this.currentIndex === this.slides.length - 1
    }
  },
  methods: {
    showPrev() {
      if (this.isFirst) return
      this.currentIndex -= 1
    },
    showNext() {
      if (this.isLast) return
      this.currentIndex += 1
    },
    goToLoad() {
      if (this.$router) {
        this.$router.push('/load');
      } else if (typeof window !== 'undefined') {
        window.location.href = '/load';
      }
    }
  }
}
</script>

<style scoped>
.pages-wrapper {
  position: relative;
  width: 100%;
  max-width: 375px;
  margin: 0 auto;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-start;
  background: linear-gradient(180deg, #f8a575 0%, #fdb083 100%);
  overflow: hidden;
  padding: 0 16px;
}

.page-slide {
  width: 100%;
  flex: 1;
  display: flex;
}

.close-btn {
  position: absolute;
  top: 12px;
  right: 12px;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  border: none;
  background: rgba(255, 255, 255, 0.25);
  color: #fff;
  font-size: 20px;
  font-weight: 600;
  cursor: pointer;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
  transition: transform 0.2s ease, opacity 0.2s ease;
  z-index: 5;
}

.close-btn:active {
  transform: scale(0.95);
  opacity: 0.8;
}
</style>

