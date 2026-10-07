<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, usePage } from "@inertiajs/vue3";
import { ref, onMounted, onUnmounted, computed, nextTick, watch } from "vue";
import axios from "axios";
import { groupBy } from 'lodash';

const page = usePage();

// Utilisateur connecté
const user = ref(page.props.authUser || null); 
const users = ref(page.props.users || []);
const activeUserClass = (id) => receiverId.value === id ? 'bg-primary-menu font-bold text-white' : '';

// Messagerie
const receiverId = ref(null);
const receiverUser = ref(null);
const messages = ref([]);
const newMessage = ref('');  
const unreadCounts = ref({})  
const activeMenuId = ref(null);
const editingMessageId = ref(null)
const isUserScrolling = ref(false);

// 🔔 NOTIFICATIONS
const showNotification = ref(false);
const notificationMessage = ref('');
const notificationSound = ref(null);

// 🔔 Son de notification
const playNotificationSound = () => {
    try {
        // Créer un contexte audio
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        
        // Créer un oscillateur
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);
        
        // Paramètres du son
        oscillator.frequency.value = 800;
        oscillator.type = 'sine';
        
        // Volume
        gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3);
        
        // Jouer le son
        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.3);
        
    } catch (error) {
        console.warn('Erreur de lecture du son:', error);
        // Fallback: utiliser un élément audio
        playFallbackSound();
    }
};
// 🔔 SON DE FALLBACK - Avec élément audio
const playFallbackSound = () => {
    try {
        // Créer un son avec l'API AudioContext (version simplifiée)
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        const bufferSize = audioCtx.sampleRate * 0.3;
        const buffer = audioCtx.createBuffer(1, bufferSize, audioCtx.sampleRate);
        const data = buffer.getChannelData(0);
        
        // Générer un son simple
        for (let i = 0; i < bufferSize; i++) {
            data[i] = Math.sin(i / 20) * Math.exp(-i / bufferSize * 3);
        }
        
        const source = audioCtx.createBufferSource();
        source.buffer = buffer;
        source.connect(audioCtx.destination);
        source.start();
        source.stop(audioCtx.currentTime + 0.3);
    } catch (e) {
        console.warn('Fallback son échoué:', e);
    }
};

// // 🔔 TEST DU SON (à appeler pour vérifier)
// const testSound = () => {
//     playNotificationSound();
//     toast.info('🔔 Son de notification testé!');
// };

// 🔔 Notification visuelle
const showToastNotification = (message, senderName) => {
    notificationMessage.value = `📩 Nouveau message de ${senderName} : ${message}`;
    showNotification.value = true;
    
    // Jouer le son
    playNotificationSound();
    
    // Masquer après 5 secondes
    setTimeout(() => {
        showNotification.value = false;
    }, 5000);
};

// 🔔 Vérifier les nouveaux messages
const checkNewMessages = async () => {
    try {
        const res = await axios.get('/messages/unread-by-user');
        const counts = {};
        let hasNewMessage = false;
        let newMessageSender = null;
        
        res.data.forEach(item => {
            counts[item.sender_id] = item.unread_count;
            if (item.unread_count > 0 && item.sender_id !== user.value?.id) {
                hasNewMessage = true;
                newMessageSender = item.sender_id;
            }
        });
        
        unreadCounts.value = counts;
        
        // 🔔 Si nouveau message non lu et que ce n'est pas l'expéditeur actuel
        if (hasNewMessage && newMessageSender && receiverId.value !== newMessageSender) {
            // Récupérer le message pour l'afficher dans la notification
            try {
                const msgRes = await axios.get(`/api/messages/${newMessageSender}`);
                const lastMsg = msgRes.data[msgRes.data.length - 1];
                if (lastMsg && lastMsg.sender_id === newMessageSender) {
                    const sender = users.value.find(u => u.id === newMessageSender);
                    showToastNotification(lastMsg.content, sender?.name || 'Utilisateur');
                }
            } catch (e) {
                console.error('Erreur récupération message:', e);
            }
        }
    } catch (err) {
        console.error('Erreur checkNewMessages:', err);
    }
};

// 🚀 POLLING AMÉLIORÉ AVEC NOTIFICATIONS
let intervalUnread;
let intervalMessages;

onMounted(() => {
    fetchUnread();
    intervalUnread = setInterval(checkNewMessages, 3000); // Vérification toutes les 3 secondes
    
    // Vérification des messages si une discussion est ouverte
    intervalMessages = setInterval(() => {
        if (receiverId.value) {
            fetchMessages();
        }
    }, 5000);
});

onUnmounted(() => {
    clearInterval(intervalUnread);
    clearInterval(intervalMessages);
});

// Watcher sur les messages pour détecter les nouveaux
watch(messages, (newMessages, oldMessages) => {
    if (newMessages.length > oldMessages.length) {
        const newMsg = newMessages[newMessages.length - 1];
        // Si le message vient de l'autre utilisateur et que ce n'est pas le focus
        if (newMsg && newMsg.sender_id !== user.value?.id && receiverId.value === newMsg.sender_id) {
            // Son uniquement si on est dans la discussion
            playNotificationSound();
        }
    }
}, { deep: true });

// 🔔 Effet de clignotement pour les nouveaux messages
const isNewMessage = (msg) => {
    if (!msg.created_at) return false;
    const msgDate = new Date(msg.created_at);
    const now = new Date();
    const diffMinutes = (now - msgDate) / 1000 / 60;
    return diffMinutes < 5 && msg.sender_id !== user.value?.id && !msg.is_read;
};

function toggleMenu(id) {
    activeMenuId.value = activeMenuId.value === id ? null : id;
}

async function deleteMessage(id) {
    try {
        await axios.delete(`/message/${id}`);
        messages.value = messages.value.filter(m => m.id !== id);
        activeMenuId.value = null;
    } catch (err) {
        console.error("Erreur suppression message:", err);
    }
}

function isAtBottom() {
    const container = document.querySelector(".messages-container");
    if (!container) return false;
    return container.scrollTop + container.clientHeight >= container.scrollHeight - 5;
}

function handleScroll() {
    const container = document.querySelector(".messages-container");
    if (!container) return;
    isUserScrolling.value = !isAtBottom();
}

function scrollToBottom() {
    nextTick(() => {
        const container = document.querySelector(".messages-container");
        if (container && !isUserScrolling.value) {
            container.scrollTop = container.scrollHeight;
        }
    });
}

async function fetchMessages() {
    if (!receiverId.value) return;

    try {
        const res = await axios.get(`/api/messages/${receiverId.value}`);
        const fetched = Array.isArray(res.data) ? res.data : [];

        const map = new Map();
        messages.value.forEach(m => map.set(Number(m.id), { ...m }));
        fetched.forEach(f => {
            const id = Number(f.id);
            const existing = map.get(id) || {};
            map.set(id, {
                ...existing,
                ...f,
                id,
                sender_id: Number(f.sender_id),
                receiver_id: Number(f.receiver_id),
                is_received: f.is_received === true || f.is_received === 1 || f.is_received === "1",
                is_read: f.is_read === true || f.is_read === 1 || f.is_read === "1",
            });
        });

        messages.value = Array.from(map.values())
            .sort((a, b) => new Date(a.created_at) - new Date(b.created_at));

        scrollToBottom();
    } catch (err) {
        console.error("Erreur fetchMessages:", err);
    }
}

const fetchUnread = async () => {
    try {
        const res = await axios.get('/messages/unread-by-user');
        const counts = {};
        res.data.forEach(item => {
            counts[item.sender_id] = item.unread_count;
        });
        unreadCounts.value = counts;
    } catch (err) {
        console.error(err);
    }
};

const otherUsersWithUnread = computed(() => {
    return users.value
        .filter(u => u.id !== user.value?.id)
        .map(u => ({
            ...u,
            unread: unreadCounts.value[u.id] || 0
        }));
});

async function selectUser(userId) {
    receiverId.value = userId;
    receiverUser.value = users.value.find(u => u.id === userId) || null;

    await fetchMessages();

    try {
        await axios.post(`/message/markRead/${userId}`);
        messages.value = messages.value.map(msg => {
            if (msg.sender_id === userId && !msg.is_read) {
                return { ...msg, is_read: true };
            }
            return msg;
        });
        unreadCounts.value[userId] = 0;
    } catch (err) {
        console.error("Erreur markRead:", err);
    }
}

function isMine(msg) {
    return msg.sender_id === user.value?.id;
}

const filteredMessages = computed(() => {
    if (!receiverId.value) return [];
    return messages.value.filter(
        m => (m.sender_id === receiverId.value && m.receiver_id === user.value.id) ||
             (m.sender_id === user.value.id && m.receiver_id === receiverId.value)
    ).sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
});

function editMessage(msg) {
    editingMessageId.value = msg.id;
    newMessage.value = msg.content;
}

function cancelEdit() {
    editingMessageId.value = null;
    newMessage.value = "";
}

async function handleSubmit() {
    const content = newMessage.value.trim();
    if (!content) return;

    if (editingMessageId.value) {
        await updateMessage(editingMessageId.value, content);
    } else {
        await sendMessage();
    }
}

async function sendMessage() {
    if (!newMessage.value.trim() || !receiverUser.value) return;
    try {
        const res = await axios.post("/message", {
            receiver_id: receiverUser.value.id,
            content: newMessage.value
        });

        messages.value.push({
            ...res.data.message,
            is_received: false,
            is_read: false
        });

        newMessage.value = "";
        scrollToBottom();
    } catch (e) {
        console.error("Erreur sendMessage:", e);
    }
}

async function updateMessage() {
    const msgToUpdate = messages.value.find(m => m.id === editingMessageId.value);
    if (!msgToUpdate) {
        toast.error('❌ Message non trouvé');
        return;
    }

    try {
        // ✅ Utiliser POST avec _method=PUT
        const res = await axios.post(`/message/${editingMessageId.value}`, {
            _method: 'PUT',
            content: newMessage.value.trim()
        });

        // Mettre à jour le message dans la liste
        const index = messages.value.findIndex(m => m.id === editingMessageId.value);
        if (index !== -1) {
            messages.value[index] = {
                ...messages.value[index],
                content: newMessage.value.trim(),
                updated_at: new Date().toISOString()
            };
        }

        // Réinitialiser l'édition
        editingMessageId.value = null;
        newMessage.value = '';
        scrollToBottom();
        toast.success('✅ Message modifié avec succès');
    } catch (err) {
        console.error("Erreur updateMessage:", err.response?.data || err.message);
        
        // Afficher un message d'erreur plus détaillé
        if (err.response?.status === 403) {
            toast.error('❌ Vous n\'êtes pas autorisé à modifier ce message');
        } else if (err.response?.status === 404) {
            toast.error('❌ Message non trouvé');
        } else {
            toast.error('❌ Erreur lors de la modification');
        }
    }
}

</script>

<template>
    <Head title="Messagerie" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-primary-txt">
                Messagerie
            </h2>
        </template>

        <div v-if="showNotification"
             class="fixed top-20 right-5 z-50 flex max-w-sm items-start gap-3 rounded-2xl bg-primary px-5 py-4 text-white shadow-2xl animate-slide-in-right">
            <div class="text-xl">🔔</div>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold">Nouveau message</p>
                <p class="text-xs text-white/80">{{ notificationMessage }}</p>
            </div>
            <button type="button" @click="showNotification = false" class="text-white/70 hover:text-white" aria-label="Fermer">✕</button>
        </div>

        <div class="px-4 py-6" v-if="user">
            <div class="mx-auto w-full max-w-8xl">
                <div class="flex h-[calc(100vh-9rem)] min-h-[560px] overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl">

                    <aside class="flex w-full max-w-xs flex-col border-r border-gray-100 bg-gray-50">
                        <div class="bg-primary px-5 py-4 text-white">
                            <p class="text-xs uppercase tracking-wide text-white/70">Connecté</p>
                            <p class="truncate text-lg font-semibold">{{ user.name }}</p>
                        </div>
                        <ul class="flex-1 space-y-1 overflow-y-auto p-3">
                            <li
                                v-for="u in otherUsersWithUnread"
                                :key="u.id"
                                @click="selectUser(u.id)"
                                class="flex cursor-pointer items-center gap-3 rounded-xl px-3 py-2.5 transition hover:bg-primary-only hover:text-primary-txt hover:shadow-sm"
                                :class="activeUserClass(u.id)"
                            >
                                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-primary text-sm font-bold text-white">
                                    {{ (u.name || '?').split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase() }}
                                </div>
                                <span class="min-w-0 flex-1 truncate">{{ u.name }}</span>
                                <span v-if="u.unread > 0"
                                      class="inline-flex min-w-[22px] items-center justify-center rounded-full bg-red-500 px-1.5 py-0.5 text-xs font-bold text-white">
                                    {{ u.unread }}
                                </span>
                            </li>
                        </ul>
                    </aside>

                    <section class="flex min-w-0 flex-1 flex-col">
                        <div class="flex items-center gap-3 border-b border-white/10 bg-primary px-5 py-4 text-white">
                            <div v-if="receiverUser" class="flex h-10 w-10 items-center justify-center rounded-full bg-white/20 text-sm font-bold">
                                {{ receiverUser.name.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase() }}
                            </div>
                            <div>
                                <p class="text-xs uppercase tracking-wide text-white/70">Discussion</p>
                                <p class="font-semibold">{{ receiverUser ? receiverUser.name : 'Sélectionnez un utilisateur' }}</p>
                            </div>
                        </div>

                        <div class="messages-container flex flex-1 flex-col space-y-3 overflow-y-auto p-5" @scroll="handleScroll">
                            <div v-if="!receiverId" class="m-auto max-w-sm rounded-2xl bg-white/90 px-6 py-8 text-center text-primary-txt shadow-sm">
                                <p class="text-lg font-semibold">Aucune discussion ouverte</p>
                                <p class="mt-1 text-sm text-primary-txt">Choisissez un utilisateur pour commencer.</p>
                            </div>
                            <div v-else-if="messages.length === 0" class="m-auto max-w-sm rounded-2xl bg-white/90 px-6 py-8 text-center text-primary-txt shadow-sm">
                                <p class="text-lg font-semibold">Conversation vide</p>
                                <p class="mt-1 text-sm text-primary-txt">Aucun message avec {{ receiverUser.name }}.</p>
                            </div>

                            <div v-for="msg in filteredMessages" :key="msg?.id" class="flex w-full"
                                 :class="msg.sender_id === user.id ? 'justify-end' : 'justify-start'">
                                <div
                                    :class="[
                                        'relative max-w-[75%] px-4 py-2.5 shadow-sm',
                                        msg.sender_id === user.id
                                            ? 'rounded-2xl rounded-br-md bg-primary-only text-primary-txt'
                                            : 'rounded-2xl rounded-bl-md border border-gray-100 bg-white text-primary-txt',
                                        isNewMessage(msg) ? 'ring-2 ring-primary-only' : ''
                                    ]"
                                >
                                    <span v-if="isNewMessage(msg)"
                                          class="absolute -top-2 -right-2 rounded-full bg-primary-only px-1.5 py-0.5 text-[10px] font-bold text-primary-txt">
                                        Nouveau
                                    </span>

                                    <p class="whitespace-pre-wrap break-words text-sm leading-relaxed">{{ msg.content }}</p>

                                    <div class="mt-1 flex items-center justify-end gap-2 text-[11px]"
                                         class="text-primary-txt/70">
                                        <span>{{ msg.created_at ? new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '' }}</span>
                                        <span v-if="msg.sender_id === user.id">{{ msg.is_read ? '✓✓' : '✓' }}</span>
                                    </div>

                                    <template v-if="isMine(msg)">
                                        <button
                                            type="button"
                                            @click="toggleMenu(msg.id)"
                                            class="absolute top-1 right-1 flex h-6 w-6 items-center justify-center rounded-full text-white/80 hover:bg-white/20"
                                            title="Options"
                                        >
                                            ⋮
                                        </button>
                                        <div
                                            v-if="activeMenuId === msg.id"
                                            class="absolute top-8 right-0 z-20 w-36 animate-fade-in rounded-xl border border-gray-100 bg-white p-1 text-primary-txt shadow-lg"
                                        >
                                            <button type="button" @click="editMessage(msg)" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-xs hover:bg-gray-50">
                                                Modifier
                                            </button>
                                            <button type="button" @click="deleteMessage(msg.id)" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-xs text-red-600 hover:bg-red-50">
                                                Supprimer
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <form v-if="receiverId" @submit.prevent="handleSubmit" class="flex items-center gap-2 border-t border-gray-100 bg-white px-4 py-3">
                            <input
                                v-model="newMessage"
                                type="text"
                                class="h-11 flex-1 rounded-full border border-gray-200 bg-gray-50 px-4 text-sm text-primary-txt outline-none transition focus:border-primary focus:bg-white focus:ring-2 focus:ring-primary/20"
                                placeholder="Écrire un message..."
                            />
                            <button
                                v-if="editingMessageId"
                                type="button"
                                @click="cancelEdit"
                                class="h-11 rounded-full border border-gray-200 px-4 text-sm font-medium text-primary-txt hover:bg-gray-50"
                            >
                                Annuler
                            </button>
                            <button
                                type="submit"
                                class="h-11 rounded-full bg-primary px-5 text-sm font-semibold text-white shadow-md transition hover:brightness-110"
                            >
                                {{ editingMessageId ? 'Mettre à jour' : 'Envoyer' }}
                            </button>
                        </form>
                    </section>
                </div>
            </div>
        </div>

        <div v-else class="py-20 text-center text-primary-txt">
            Chargement de l'utilisateur...
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
@keyframes fadeIn {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}

@keyframes slideInRight {
    from { transform: translateX(100px); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

.animate-fade-in {
    animation: fadeIn 0.15s ease-out;
}

.animate-slide-in-right {
    animation: slideInRight 0.5s ease-out;
}

.animate-pulse {
    animation: pulse 1.5s ease-in-out infinite;
}

.messages-container {
    scroll-behavior: smooth;
    background-color: #f4f7fb;
    background-image:
        radial-gradient(circle at 15% 20%, rgba(255, 122, 24, 0.10), transparent 42%),
        radial-gradient(circle at 85% 0%, rgba(106, 17, 203, 0.08), transparent 36%);
}

/* Scrollbar personnalisée */
.messages-container::-webkit-scrollbar {
    width: 6px;
}

.messages-container::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.messages-container::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 3px;
}

.messages-container::-webkit-scrollbar-thumb:hover {
    background: #555;
}
</style>