<script setup>
import { usePage } from "@inertiajs/vue3";
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from "vue";
import axios from "axios";
import BaseInput from "@/Components/BaseInput.vue";

const page = usePage();
const user = computed(() => page.props.auth?.user || null);
const users = computed(() => page.props.chatUsers || []);

const open = ref(false);
const receiverId = ref(null);
const receiverUser = ref(null);
const messages = ref([]);
const newMessage = ref("");
const unreadCounts = ref({});
const activeMenuId = ref(null);
const editingMessageId = ref(null);
const isUserScrolling = ref(false);
const messagesEl = ref(null);

const unreadTotal = computed(() =>
    Object.values(unreadCounts.value).reduce((sum, n) => sum + Number(n || 0), 0)
);

const otherUsers = computed(() =>
    users.value.map((u) => ({
        ...u,
        unread: unreadCounts.value[u.id] || 0,
    }))
);

function sameId(a, b) {
    return Number(a) === Number(b);
}

function formatWhen(value) {
    if (!value) return "";
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return "";
    const time = date.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" });
    const today = new Date();
    if (date.toDateString() === today.toDateString()) return time;
    return `${date.toLocaleDateString()} ${time}`;
}

function dayKey(value) {
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return "";
    return date.toDateString();
}

const filteredMessages = computed(() => {
    if (!receiverId.value || !user.value) return [];
    const me = Number(user.value.id);
    const other = Number(receiverId.value);
    return messages.value
        .filter((m) => {
            const sender = Number(m.sender_id);
            const receiver = Number(m.receiver_id);
            return (sender === other && receiver === me) || (sender === me && receiver === other);
        })
        .sort((a, b) => new Date(a.created_at) - new Date(b.created_at) || Number(a.id) - Number(b.id));
});

function initials(name) {
    return String(name || "?")
        .split(" ")
        .filter(Boolean)
        .map((part) => part[0])
        .join("")
        .slice(0, 2)
        .toUpperCase();
}

function publishState() {
    window.dispatchEvent(new CustomEvent("chat-plugin-state", { detail: open.value }));
    window.dispatchEvent(new CustomEvent("chat-unread-total", { detail: unreadTotal.value }));
}

function togglePlugin() {
    open.value = !open.value;
    publishState();
}

function openPlugin() {
    open.value = true;
    publishState();
}

function closePlugin() {
    open.value = false;
    activeMenuId.value = null;
    publishState();
}

function isAtBottom() {
    const container = messagesEl.value;
    if (!container) return true;
    return container.scrollTop + container.clientHeight >= container.scrollHeight - 8;
}

function handleScroll() {
    isUserScrolling.value = !isAtBottom();
}

function scrollToBottom() {
    nextTick(() => {
        const container = messagesEl.value;
        if (container && !isUserScrolling.value) {
            container.scrollTop = container.scrollHeight;
        }
    });
}

async function fetchUnread() {
    if (!user.value) return;
    try {
        const res = await axios.get("/messages/unread-by-user");
        const counts = {};
        (res.data || []).forEach((item) => {
            counts[item.sender_id] = item.unread_count;
        });
        unreadCounts.value = counts;
        publishState();
    } catch (err) {
        console.error(err);
    }
}

async function fetchMessages() {
    if (!receiverId.value) return;
    try {
        const res = await axios.get(`/api/messages/${receiverId.value}`);
        const fetched = Array.isArray(res.data) ? res.data : [];
        const map = new Map();
        messages.value.forEach((m) => map.set(Number(m.id), { ...m }));
        fetched.forEach((f) => {
            const id = Number(f.id);
            map.set(id, {
                ...(map.get(id) || {}),
                ...f,
                id,
                sender_id: Number(f.sender_id),
                receiver_id: Number(f.receiver_id),
                is_read: f.is_read === true || f.is_read === 1 || f.is_read === "1",
            });
        });
        messages.value = Array.from(map.values()).sort(
            (a, b) => new Date(a.created_at) - new Date(b.created_at)
        );
        scrollToBottom();
    } catch (err) {
        console.error("Erreur fetchMessages:", err);
    }
}

async function selectUser(userId) {
    receiverId.value = userId;
    receiverUser.value = users.value.find((u) => u.id === userId) || null;
    isUserScrolling.value = false;
    activeMenuId.value = null;
    editingMessageId.value = null;
    newMessage.value = "";
    messages.value = [];
    await fetchMessages();
    try {
        await axios.post(`/message/markRead/${userId}`);
        unreadCounts.value = { ...unreadCounts.value, [userId]: 0 };
        publishState();
    } catch (err) {
        console.error("Erreur markRead:", err);
    }
}

function backToList() {
    receiverId.value = null;
    receiverUser.value = null;
    editingMessageId.value = null;
    newMessage.value = "";
    activeMenuId.value = null;
}

function toggleMenu(id) {
    activeMenuId.value = activeMenuId.value === id ? null : id;
}

async function deleteMessage(id) {
    try {
        await axios.delete(`/message/${id}`);
        messages.value = messages.value.filter((m) => m.id !== id);
        activeMenuId.value = null;
    } catch (err) {
        console.error("Erreur suppression message:", err);
    }
}

function editMessage(msg) {
    editingMessageId.value = msg.id;
    newMessage.value = msg.content;
    activeMenuId.value = null;
}

function cancelEdit() {
    editingMessageId.value = null;
    newMessage.value = "";
}

async function sendMessage() {
    if (!newMessage.value.trim() || !receiverUser.value) return;
    try {
        const res = await axios.post("/message", {
            receiver_id: receiverUser.value.id,
            content: newMessage.value,
        });
        messages.value.push({
            ...res.data.message,
            is_read: false,
        });
        newMessage.value = "";
        isUserScrolling.value = false;
        scrollToBottom();
    } catch (err) {
        console.error("Erreur sendMessage:", err);
    }
}

async function updateMessage(id, content) {
    try {
        const res = await axios.put(`/message/${id}`, { content });
        const index = messages.value.findIndex((m) => m.id === id);
        if (index !== -1) {
            messages.value[index] = {
                ...messages.value[index],
                ...(res.data.message || { content }),
            };
        }
        cancelEdit();
    } catch (err) {
        console.error("Erreur updateMessage:", err);
    }
}

async function handleSubmit() {
    const content = newMessage.value.trim();
    if (!content) return;
    if (editingMessageId.value) {
        await updateMessage(editingMessageId.value, content);
        return;
    }
    await sendMessage();
}

watch(open, (isOpen) => {
    if (isOpen && receiverId.value) {
        isUserScrolling.value = false;
        fetchMessages();
    }
});

let timer;
onMounted(() => {
    fetchUnread();
    timer = setInterval(async () => {
        await fetchUnread();
        if (open.value && receiverId.value) {
            await fetchMessages();
            if ((unreadCounts.value[receiverId.value] || 0) > 0) {
                try {
                    await axios.post(`/message/markRead/${receiverId.value}`);
                    unreadCounts.value = { ...unreadCounts.value, [receiverId.value]: 0 };
                    publishState();
                } catch (err) {
                    console.error("Erreur markRead:", err);
                }
            }
        }
    }, 4000);
    window.addEventListener("open-chat-plugin", openPlugin);
});

onUnmounted(() => {
    clearInterval(timer);
    window.removeEventListener("open-chat-plugin", openPlugin);
});
</script>

<template>
    <div v-if="user" class="chat-plugin">
        <section v-if="open" class="chat-panel" aria-label="Discussion">
            <header class="chat-head">
                <button
                    v-if="receiverUser"
                    type="button"
                    class="chat-icon-btn"
                    @click="backToList"
                    aria-label="Retour aux utilisateurs"
                >
                    ←
                </button>
                <div class="min-w-0">
                    <p class="chat-kicker text-white">Discussion</p>
                    <p class="chat-title text-white">
                        {{ receiverUser ? receiverUser.name : "Choisir un utilisateur" }}
                    </p>
                </div>
                <button type="button" class="chat-icon-btn hover:text-white hover:bg-danger-500" @click="closePlugin" aria-label="Fermer">
                    ❌
                </button>
            </header>

            <ul v-if="!receiverId" class="chat-users">
                <li v-if="otherUsers.length === 0" class="chat-empty text-primary-txt">Aucun autre utilisateur</li>
                <li v-for="u in otherUsers" :key="u.id">
                    <button type="button" class="chat-user" @click="selectUser(u.id)">
                        <span class="chat-avatar">{{ initials(u.name) }}</span>
                        <span class="truncate">{{ u.name }}</span>
                        <span v-if="u.unread > 0" class="chat-badge">{{ u.unread }}</span>
                    </button>
                </li>
            </ul>

            <template v-else>
                <div ref="messagesEl" class="chat-messages" @scroll="handleScroll">
                    <p v-if="filteredMessages.length === 0" class="chat-empty text-primary-txt">
                        Aucun message avec {{ receiverUser.name }}
                    </p>
                    <template v-for="(msg, index) in filteredMessages" :key="msg.id">
                    <p
                        v-if="index === 0 || dayKey(msg.created_at) !== dayKey(filteredMessages[index - 1].created_at)"
                        class="chat-day text-primary-txt"
                    >
                        {{ new Date(msg.created_at).toLocaleDateString() }}
                    </p>
                    <div
                        class="chat-row text-primary-txt"
                        :class="sameId(msg.sender_id, user.id) ? 'is-mine' : ''"
                    >
                        <div class="chat-bubble text-primary-txt" :class="sameId(msg.sender_id, user.id) ? 'is-mine' : ''">
                            <p>{{ msg.content }}</p>
                            <div class="chat-meta">
                                <span>{{ formatWhen(msg.created_at) }}</span>
                                <span v-if="sameId(msg.sender_id, user.id)">{{ msg.is_read ? '✓✓' : '✓' }}</span>
                            </div>
                            <button
                                v-if="sameId(msg.sender_id, user.id)"
                                type="button"
                                class="chat-more"
                                @click="toggleMenu(msg.id)"
                                aria-label="Options du message"
                            >
                                ⋮
                            </button>
                            <div v-if="activeMenuId === msg.id" class="chat-menu">
                                <button type="button" class="text-primary-txt" @click="editMessage(msg)">Modifier</button>
                                <button type="button" class="text-danger-500" @click="deleteMessage(msg.id)">Supprimer</button>
                            </div>
                        </div>
                    </div>
                    </template>
                </div>

                <form class="chat-composer" @submit.prevent="handleSubmit">
                    <BaseInput
                        class="min-w-0 flex-1"
                        name="chat-message"
                        v-model="newMessage"
                        :placeholder="editingMessageId ? 'Modifier le message…' : 'Votre message…'"
                    />
                    <button v-if="editingMessageId" type="button" class="is-ghost" @click="cancelEdit">
                        Annuler
                    </button>
                    <button type="submit">
                        {{ editingMessageId ? 'OK' : 'Envoyer' }}
                    </button>
                </form>
            </template>
        </section>

        <button type="button" class="chat-launcher" @click="togglePlugin" :aria-expanded="open">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-3 12H7v-2h10v2zm0-3H7V9h10v2zm0-3H7V6h10v2z"/>
            </svg>
            <span>Discussion</span>
            <span v-if="unreadTotal > 0" class="chat-badge">{{ unreadTotal }}</span>
        </button>
    </div>
</template>

<style scoped>
.chat-plugin {
    position: fixed;
    right: 1.25rem;
    bottom: 1.25rem;
    z-index: 60;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.75rem;
}

.chat-launcher {
    display: inline-flex;
    align-items: center;
    gap: 0.55rem;
    border: 0;
    border-radius: 999px;
    padding: 0.8rem 1.05rem;
    background: linear-gradient(180deg, #c7812e, #613104);
    color: #fff8f1;
    font-weight: 700;
    box-shadow: 0 12px 30px rgba(122, 62, 18, 0.35);
}

.chat-launcher svg {
    width: 1.15rem;
    height: 1.15rem;
}

.chat-panel {
    display: flex;
    width: min(24rem, calc(100vw - 2rem));
    height: min(34rem, calc(100vh - 7rem));
    flex-direction: column;
    overflow: hidden;
    border: 1px solid #e6c5a2;
    border-radius: 1.15rem;
    background: #fff;
    box-shadow: 0 18px 50px rgba(44, 24, 16, 0.22);
}

.chat-head {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.85rem 0.9rem;
    background: linear-gradient(180deg, #a35200, #613104);
    color: #fff8f1;
}

.chat-kicker {
    font-size: 0.68rem;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    opacity: 0.75;
}

.chat-title {
    overflow: hidden;
    font-family: "Source Serif 4", Georgia, serif;
    font-size: 1.05rem;
    line-height: 1.2;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.chat-icon-btn {
    display: grid;
    width: 2rem;
    height: 2rem;
    flex-shrink: 0;
    place-items: center;
    border: 0;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.14);
    color: white;
    font-size: 1.1rem;
}

.chat-users,
.chat-messages {
    min-height: 0;
    flex: 1;
    overflow-y: auto;
}

.chat-users {
    padding: 0.45rem;
}

.chat-user {
    display: flex;
    width: 100%;
    align-items: center;
    gap: 0.7rem;
    border: 0;
    border-radius: 0.8rem;
    padding: 0.55rem 0.6rem;
    background: transparent;
    color: #3f2410;
    text-align: left;
}

.chat-user:hover {
    background: #ffe5c5;
}

.chat-avatar {
    display: grid;
    width: 2rem;
    height: 2rem;
    flex-shrink: 0;
    place-items: center;
    border-radius: 999px;
    background: #613104;
    color: white;
    font-size: 0.75rem;
    font-weight: 700;
}

.chat-badge {
    margin-left: auto;
    border-radius: 999px;
    background: #1f8a4c;
    padding: 0.1rem 0.45rem;
    color: white;
    font-size: 0.72rem;
    font-weight: 700;
}

.chat-messages {
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
    padding: 0.8rem;
    background: #faefe6;
}

.chat-row {
    display: flex;
    justify-content: flex-start;
}

.chat-row.is-mine {
    justify-content: flex-end;
}

.chat-bubble {
    position: relative;
    max-width: 85%;
    border-radius: 0.95rem;
    background: white;
    padding: 0.45rem 1.4rem 0.4rem 0.7rem;
    color: #3f2410;
    box-shadow: 0 1px 0 rgba(44, 24, 16, 0.05);
}

.chat-bubble.is-mine {
    background: #d9f5e3;
}

.chat-bubble p {
    font-size: 0.9rem;
    line-height: 1.35;
    white-space: pre-wrap;
}

.chat-meta {
    display: flex;
    justify-content: space-between;
    gap: 0.6rem;
    margin-top: 0.2rem;
    color: #7a5a40;
    font-size: 0.68rem;
}

.chat-more {
    position: absolute;
    top: 0.15rem;
    right: 0.15rem;
    border: 0;
    background: transparent;
    color: #3f2410;
    font-weight: 700;
}

.chat-menu {
    position: absolute;
    top: 1.4rem;
    right: 0;
    z-index: 2;
    display: grid;
    min-width: 7rem;
    overflow: hidden;
    border: 1px solid #e6c5a2;
    border-radius: 0.7rem;
    background: white;
    box-shadow: 0 8px 24px rgba(44, 24, 16, 0.12);
}

.chat-menu button {
    border: 0;
    background: white;
    padding: 0.45rem 0.7rem;
    text-align: left;
    font-size: 0.82rem;
}

.chat-menu button.is-danger {
    color: #9b3a2f;
}

.chat-day {
    align-self: center;
    margin: 0.35rem 0;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.85);
    padding: 0.15rem 0.6rem;
    color: #7a5a40;
    font-size: 0.72rem;
}

.chat-empty {
    padding: 1.5rem 0.5rem;
    color: #7a5a40;
    text-align: center;
    font-size: 0.9rem;
}

.chat-composer {
    display: flex;
    gap: 0.4rem;
    border-top: 1px solid #e6c5a2;
    padding: 0.65rem;
    background: white;
}

.chat-composer input {
    min-width: 0;
    flex: 1;
    border: 1px solid #e6c5a2;
    border-radius: 0.7rem;
    padding: 0.55rem 0.7rem;
    font-size: 0.9rem;
}

.chat-composer button {
    border: 0;
    border-radius: 0.7rem;
    background: #613104;
    padding: 0.55rem 0.75rem;
    color: white;
    font-size: 0.85rem;
    font-weight: 700;
}

.chat-composer button.is-ghost {
    background: #c7812e;
}
</style>
