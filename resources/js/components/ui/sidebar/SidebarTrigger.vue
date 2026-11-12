<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { Menu, PanelLeftClose, PanelLeftOpen } from "@lucide/vue"
import { cn } from "@/lib/utils"
import { Button } from '@/components/ui/button'
import { useSidebar } from "./utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()

const { isMobile, state, openMobile, toggleSidebar } = useSidebar()
</script>

<template>
  <Button
    data-sidebar="trigger"
    data-slot="sidebar-trigger"
    variant="ghost"
    size="icon"
    :class="cn('h-10 w-10', props.class)"
    @click="toggleSidebar"
  >
    <Menu v-if="isMobile" class="size-5" />
    <PanelLeftOpen v-else-if="state === 'collapsed'" />
    <PanelLeftClose v-else />
    <span class="sr-only">{{ isMobile ? (openMobile ? 'Đóng menu điều hướng' : 'Mở menu điều hướng') : 'Thu gọn menu điều hướng' }}</span>
  </Button>
</template>
