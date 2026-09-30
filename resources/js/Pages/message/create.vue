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

        <!-- 🔔 NOTIFICATION TOAST -->
        <div v-if="showNotification" 
             class="fixed top-20 right-5 z-50 bg-green-500 text-white px-6 py-4 rounded-xl shadow-2xl 
                    flex items-center gap-3 animate-slide-in-right transition-all duration-500"
             style="max-width: 400px; min-width: 300px;">
            <div class="text-2xl animate-bounce">🔔</div>
            <div class="flex-1">
                <p class="font-semibold text-sm">Nouveau message</p>
                <p class="text-xs opacity-90">{{ notificationMessage }}</p>
            </div>
            <button @click="showNotification = false" class="text-white/70 hover:text-white text-xl">✕</button>
        </div>

        <div class="py-12" v-if="user">
            <div class="flex justify-center">
                <div class="w-full max-w-8xl">
                    <div class="h-[700px] bg-white border shadow-md flex rounded-xl">

                        <!-- Sidebar utilisateurs -->
                        <div class="w-1/3 border-r bg-white flex flex-col rounded-xl">
                            <div class="p-4 border-b bg-primary text-white font-semibold rounded-tl-xl">
                                Utilisateurs : {{ user.name }}
                            </div>
                            <ul class="overflow-y-auto flex-1">
                                <li
                                    v-for="u in otherUsersWithUnread"
                                    :key="u.id"
                                    @click="selectUser(u.id)"
                                    class="hover:bg-primary-only hover:text-primary-txt hover:font-semibold hover:border hover:text-white p-2 rounded-xl flex items-center justify-between cursor-pointer"
                                    :class="activeUserClass(u.id)"
                                >
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 flex-shrink-0 flex items-center justify-center mr-3 rounded-full bg-primary text-white font-bold">
                                            {{ u.name.split(' ').map(n => n[0]).join('').toUpperCase() }}
                                        </div>
                                        <span>{{ u.name }}</span>

                                        <!-- 🔔 Badge avec animation -->
                                        <span v-if="u.unread > 0" 
                                              class="bg-green-500 text-white text-xs font-bold px-2 py-0.5 rounded-full ml-2 animate-pulse">
                                            {{ u.unread }}
                                        </span>
                                    </div>
                                </li>
                            </ul>
                        </div>

                        <!-- Zone discussion -->
                        <div class="w-2/3 flex flex-col">
                            <!-- Header discussion -->
                            <div class="p-4 border-b bg-primary text-white font-semibold rounded-tr-xl">
                                <span v-if="receiverUser">Discussion avec {{ receiverUser.name }}</span>
                                <span v-else>Sélectionnez un utilisateur</span>
                            </div>

                            <!-- Liste messages -->
                            <div class="messages-container flex-1 p-4 overflow-y-auto flex flex-col space-y-2 bg-gray-50"
                                 style="background-image: url('/discut.jpg'); background-size: cover; background-position: center; padding-bottom: 1rem;"
                                 @scroll="handleScroll">

                                <div v-if="!receiverId" class="text-gray-500 text-center mt-10">
                                    Choisissez un utilisateur pour commencer la discussion
                                </div>
                                <div v-else-if="messages.length === 0" class="text-primary-txt text-center mt-10">
                                    Aucun message avec {{ receiverUser.name }}
                                </div>

                                <div v-for="msg in filteredMessages" :key="msg?.id" class="flex w-full items-center relative"
                                     :class="msg.sender_id === user.id ? 'justify-end' : 'justify-start'">
                                    <div 
                                        :class="[
                                            'px-3 py-2 max-w-xs rounded-xl relative', 
                                            msg.sender_id === user.id 
                                                ? 'bg-green-200 text-gray-800' 
                                                : 'bg-gray-200 text-gray-800',
                                            isNewMessage(msg) ? 'border-2 border-blue-400 animate-pulse' : ''
                                        ]"
                                    >
                                        <!-- 🔔 Indicateur nouveau message -->
                                        <span v-if="isNewMessage(msg)" 
                                              class="absolute -top-2 -right-2 bg-blue-500 text-white text-[8px] font-bold px-1.5 py-0.5 rounded-full animate-bounce">
                                            NEW
                                        </span>

                                        <p class="text-sm">{{ msg.content }}</p>

                                        <div class="w-full flex justify-between items-end mt-1">
                                            <span class="text-xs opacity-70">
                                                {{ msg.created_at ? new Date(msg.created_at).toLocaleTimeString() : '' }}
                                            </span>

                                            <span v-if="msg.sender_id === user.id" 
                                                  class="text-xs"
                                                  :class="msg.is_read ? 'text-blue-500' : 'text-blue-400'">
                                                {{ msg.is_read ? '✓✓' : '✓' }}
                                            </span>
                                        </div>

                                        <!-- Trois points et menu uniquement pour tes messages -->
                                        <template v-if="isMine(msg)">
                                            <span 
                                                @click="toggleMenu(msg.id)" 
                                                class="absolute top-0 right-0 cursor-pointer text-black font-bold hover:text-primary-txt
                                                       w-6 h-6 flex items-center justify-center rounded-full hover:bg-primary-only transition-colors duration-200"
                                                title="Options"
                                            >
                                                ⋮
                                            </span>
                                            <div 
                                                v-if="activeMenuId === msg.id" 
                                                class="absolute top-1 right-10 bg-white p-2 shadow-lg border rounded-lg z-20 w-32 animate-fade-in"
                                            >
                                                <ul class="text-xs">
                                                    <li 
                                                        @click="deleteMessage(msg.id)" 
                                                        class="px-3 py-2 hover:bg-red-100 cursor-pointer border-b border-primary-only text-red-500 flex items-center gap-2 rounded-md transition"
                                                    >
                                                        🗑 Supprimer
                                                    </li>
                                                    <li 
                                                        @click="editMessage(msg)" 
                                                        class="px-3 py-2 hover:bg-green-100 cursor-pointer text-green-600 flex items-center gap-2 rounded-md transition"
                                                    >
                                                        ✏️ Modifier
                                                    </li>
                                                </ul>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <!-- Formulaire -->
                            <form v-if="receiverId" @submit.prevent="handleSubmit" class="p-4 border-primary-only flex space-x-2 bg-white rounded-br-xl">
                                <input 
                                    v-model="newMessage" 
                                    type="text" 
                                    class="h-8 flex-1 border border-primary-only rounded px-3 py-2"
                                    placeholder="Écrire un message..."
                                />
                                <button 
                                    type="submit" 
                                    class="w-36 h-8 text-white bg-gradient-to-r from-primary via-primary-dark to-primary
                                           hover:bg-gradient-to-br focus:ring-4 focus:outline-none focus:ring-blue-300
                                           dark:focus:ring-blue-800 shadow-lg shadow-blue-500/50 dark:shadow-lg
                                           dark:shadow-blue-800/80 font-medium rounded-lg text-xs px-3 py-1 text-center me-2 mb-2"
                                >
                                    {{ editingMessageId ? 'Mettre à jour' : 'Envoyer' }}
                                </button>

                                <button 
                                    v-if="editingMessageId" 
                                    type="button" 
                                    @click="cancelEdit" 
                                    class="bg-gray-400 text-white px-3 py-2 rounded"
                                >
                                    Retour
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="text-center py-20 text-gray-500">
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