<script setup lang="ts">
import { Editor, EditorContent } from '@tiptap/vue-3';
import Image from '@tiptap/extension-image';
import StarterKit from '@tiptap/starter-kit';
import { Bold, Heading2, Heading3, ImagePlus, Italic, Link2, List, ListOrdered, Quote, Strikethrough, Unlink } from '@lucide/vue';
import { onBeforeUnmount, ref, shallowRef, watch } from 'vue';

const props = defineProps<{ modelValue: string }>();
const emit = defineEmits<{ 'update:modelValue': [value: string] }>();

const editor = shallowRef<Editor | null>(null);
const imageInput = ref<HTMLInputElement | null>(null);
const revision = ref(0);
const uploadError = ref('');
const uploading = ref(false);

editor.value = new Editor({
    content: props.modelValue,
    extensions: [
        StarterKit.configure({ heading: { levels: [2, 3, 4] } }),
        Image.configure({ inline: false, allowBase64: false }),
    ],
    editorProps: {
        attributes: {
            class: 'blog-editor-content',
            'aria-label': 'Nội dung bài viết',
        },
    },
    onUpdate: ({ editor: activeEditor }) => emit('update:modelValue', activeEditor.getHTML()),
    onSelectionUpdate: () => { revision.value++; },
    onTransaction: () => { revision.value++; },
});

watch(() => props.modelValue, (value) => {
    if (editor.value && value !== editor.value.getHTML()) {
        editor.value.commands.setContent(value, { emitUpdate: false });
    }
});

onBeforeUnmount(() => editor.value?.destroy());

function run(command: () => void) {
    command();
    revision.value++;
}

function toggleLink() {
    const activeEditor = editor.value;
    if (!activeEditor) return;
    if (activeEditor.isActive('link')) {
        run(() => activeEditor.chain().focus().unsetLink().run());
        return;
    }

    const href = window.prompt('Nhập địa chỉ liên kết (https://...)');
    if (!href) return;
    run(() => activeEditor.chain().focus().extendMarkRange('link').setLink({ href }).run());
}

async function uploadImage(event: Event) {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    input.value = '';
    if (!file || !editor.value) return;

    uploadError.value = '';
    uploading.value = true;
    try {
        const form = new FormData();
        form.append('image', file);
        const csrf = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content;
        const response = await fetch('/admin/news/images', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { Accept: 'application/json', ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}) },
            body: form,
        });
        const result = await response.json();
        if (!response.ok || typeof result.url !== 'string') {
            throw new Error(result.message || 'Không thể tải ảnh lên. Hãy thử lại.');
        }

        const alt = window.prompt('Mô tả ảnh (alt text)', file.name.replace(/\.[^.]+$/, '')) ?? '';
        editor.value.chain().focus().setImage({ src: result.url, alt }).run();
    } catch (error) {
        uploadError.value = error instanceof Error ? error.message : 'Không thể tải ảnh lên.';
    } finally {
        uploading.value = false;
    }
}
</script>

<template>
    <div class="blog-editor">
        <div class="blog-editor-toolbar" role="toolbar" aria-label="Định dạng nội dung" :data-revision="revision">
            <button type="button" title="In đậm" aria-label="In đậm" :aria-pressed="editor?.isActive('bold')" @click="run(() => editor?.chain().focus().toggleBold().run())"><Bold class="size-4" /></button>
            <button type="button" title="In nghiêng" aria-label="In nghiêng" :aria-pressed="editor?.isActive('italic')" @click="run(() => editor?.chain().focus().toggleItalic().run())"><Italic class="size-4" /></button>
            <button type="button" title="Gạch ngang" aria-label="Gạch ngang" :aria-pressed="editor?.isActive('strike')" @click="run(() => editor?.chain().focus().toggleStrike().run())"><Strikethrough class="size-4" /></button>
            <span class="blog-editor-divider" aria-hidden="true" />
            <button type="button" title="Tiêu đề cấp 2" aria-label="Tiêu đề cấp 2" :aria-pressed="editor?.isActive('heading', { level: 2 })" @click="run(() => editor?.chain().focus().toggleHeading({ level: 2 }).run())"><Heading2 class="size-4" /></button>
            <button type="button" title="Tiêu đề cấp 3" aria-label="Tiêu đề cấp 3" :aria-pressed="editor?.isActive('heading', { level: 3 })" @click="run(() => editor?.chain().focus().toggleHeading({ level: 3 }).run())"><Heading3 class="size-4" /></button>
            <button type="button" title="Danh sách chấm đầu dòng" aria-label="Danh sách chấm đầu dòng" :aria-pressed="editor?.isActive('bulletList')" @click="run(() => editor?.chain().focus().toggleBulletList().run())"><List class="size-4" /></button>
            <button type="button" title="Danh sách đánh số" aria-label="Danh sách đánh số" :aria-pressed="editor?.isActive('orderedList')" @click="run(() => editor?.chain().focus().toggleOrderedList().run())"><ListOrdered class="size-4" /></button>
            <button type="button" title="Trích dẫn" aria-label="Trích dẫn" :aria-pressed="editor?.isActive('blockquote')" @click="run(() => editor?.chain().focus().toggleBlockquote().run())"><Quote class="size-4" /></button>
            <span class="blog-editor-divider" aria-hidden="true" />
            <button type="button" title="Thêm liên kết" aria-label="Thêm liên kết" :aria-pressed="editor?.isActive('link')" @click="toggleLink"><Link2 class="size-4" /></button>
            <button v-if="editor?.isActive('link')" type="button" title="Gỡ liên kết" aria-label="Gỡ liên kết" @click="run(() => editor?.chain().focus().unsetLink().run())"><Unlink class="size-4" /></button>
            <button type="button" title="Tải ảnh lên" aria-label="Tải ảnh lên" :disabled="uploading" @click="imageInput?.click()"><ImagePlus class="size-4" /></button>
            <input ref="imageInput" class="sr-only" type="file" accept="image/jpeg,image/png,image/webp" @change="uploadImage" />
        </div>
        <EditorContent v-if="editor" :editor="editor" />
        <p v-if="uploading" class="blog-editor-message">Đang tải ảnh lên…</p>
        <p v-if="uploadError" class="blog-editor-error" role="alert">{{ uploadError }}</p>
    </div>
</template>

<style>
.blog-editor { overflow: hidden; border: 1px solid #3f3f46; border-radius: .5rem; background: #0b0c0b; }
.blog-editor:focus-within { border-color: #f97316; }
.blog-editor-toolbar { display: flex; flex-wrap: wrap; gap: .25rem; border-bottom: 1px solid #3f3f46; padding: .5rem; }
.blog-editor-toolbar button { display: grid; width: 2.25rem; height: 2.25rem; place-items: center; border-radius: .375rem; color: #d4d4d8; }
.blog-editor-toolbar button:hover, .blog-editor-toolbar button[aria-pressed="true"] { background: #292524; color: #fdba74; }
.blog-editor-toolbar button:disabled { cursor: wait; opacity: .5; }
.blog-editor-divider { width: 1px; margin: .25rem .25rem; background: #3f3f46; }
.blog-editor-content { min-height: 24rem; padding: .75rem; outline: none; color: #e4e4e7; font-size: .875rem; line-height: 1.75; }
.blog-editor-content p { margin: 0 0 .75rem; }
.blog-editor-content h2 { margin: 1.5rem 0 .5rem; font-size: 1.35rem; font-weight: 700; }
.blog-editor-content h3, .blog-editor-content h4 { margin: 1.25rem 0 .5rem; font-size: 1.1rem; font-weight: 700; }
.blog-editor-content ul, .blog-editor-content ol { margin: .5rem 0 .75rem; padding-left: 1.5rem; }
.blog-editor-content ul { list-style: disc; }
.blog-editor-content ol { list-style: decimal; }
.blog-editor-content blockquote { margin: 1rem 0; border-left: 3px solid #f97316; padding-left: 1rem; color: #a1a1aa; }
.blog-editor-content a { color: #fdba74; text-decoration: underline; }
.blog-editor-content img { display: block; max-width: 100%; height: auto; margin: 1rem auto; border-radius: .5rem; }
.blog-editor-message, .blog-editor-error { padding: .25rem .75rem .75rem; font-size: .75rem; }
.blog-editor-message { color: #a1a1aa; }
.blog-editor-error { color: #fca5a5; }
</style>
