<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, usePage } from "@inertiajs/vue3";
import { ref, onMounted, onUnmounted, computed, nextTick } from "vue";
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
const activeMenuId = ref(null); // ID du message dont le menu est ouvert
const editingMessageId = ref(null)
 
// Scroll automatique
const isUserScrolling = ref(false);

function toggleMenu(id) {
  activeMenuId.value = activeMenuId.value === id ? null : id;
}
 
// Supprimer un message
async function deleteMessage(id) { 

  try {
    await axios.delete(`/message/${id}`);
    messages.value = messages.value.filter(m => m.id !== id);
    activeMenuId.value = null; // fermer le menu
  } catch (err) {
    console.error("Erreur suppression message:", err);
  }
}

// Vérifie si on est tout en bas
function isAtBottom() {
  const container = document.querySelector(".messages-container");
  if (!container) return false;
  return (
    container.scrollTop + container.clientHeight >=
    container.scrollHeight - 5
  );
}

// Gérer le scroll manuel de l’utilisateur
function handleScroll() {
  const container = document.querySelector(".messages-container");
  if (!container) return;
  isUserScrolling.value = !isAtBottom();
}

// Scroll auto uniquement si on est déjà en bas
function scrollToBottom() {
  nextTick(() => {
    const container = document.querySelector(".messages-container");
    if (container && !isUserScrolling.value) {
      container.scrollTop = container.scrollHeight;
    }
  });
}
 
// Charger messages 
async function fetchMessages() {
  if (!receiverId.value) return;

  try {
    const res = await axios.get(`/api/messages/${receiverId.value}`);
    const fetched = Array.isArray(res.data) ? res.data : [];

    // Merge messages pour éviter doublons
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

    // Convertir en tableau et trier par date
    messages.value = Array.from(map.values())
    .sort((a, b) => new Date(a.created_at) - new Date(b.created_at));

    scrollToBottom();
  } catch (err) {
    console.error("Erreur fetchMessages:", err);
  }
}
 
// Charger les messages non lus
const fetchUnread = async () => {
  try {
    const res = await axios.get('/messages/unread-by-user');
    const counts = {};
    res.data.forEach(item => {
      counts[item.sender_id] = item.unread_count;
    });
    unreadCounts.value = counts; // Important : réactif
  } catch (err) {
    console.error(err);
  }
};

// Polling toutes les 2s
let intervalUnread;
onMounted(() => {
  fetchUnread();
  intervalUnread = setInterval(fetchUnread, 2000);
});
onUnmounted(() => clearInterval(intervalUnread));
 
 
// Computed pour ajouter le nombre de non lus à chaque utilisateur
const otherUsersWithUnread = computed(() => {
  return users.value
    .filter(u => u.id !== user.value?.id)
    .map(u => ({
      ...u,
      unread: unreadCounts.value[u.id] || 0
    }));
});
 
// Lorsque l’utilisateur est sélectionné, réinitialiser son compteur
async function selectUser(userId) {
  receiverId.value = userId;
  receiverUser.value = users.value.find(u => u.id === userId) || null;

  await fetchMessages();
  
  try {

    await axios.post(`/message/markRead/${userId}`);

    // Mettre à jour localement les messages pour afficher ✅
    messages.value = messages.value.map(msg => {
      if(msg.sender_id === userId && !msg.is_read) {
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
 
// Messages filtrés pour la discussion courante
const filteredMessages = computed(() => {
  if (!receiverId.value) return [];
  return messages.value.filter(
    m => (m.sender_id === receiverId.value && m.receiver_id === user.value.id) ||
         (m.sender_id === user.value.id && m.receiver_id === receiverId.value)
  ).sort((a,b) => new Date(a.created_at) - new Date(b.created_at));
});
  
// Activer l’édition
function editMessage(msg) {
  editingMessageId.value = msg.id;
  newMessage.value = msg.content; 
}
 
// Annuler l’édition
function cancelEdit() {
  editingMessageId.value = null
  newMessage.value = ""
}

// Soumission du formulaire 
async function handleSubmit() {
  const content = newMessage.value.trim();
  if (!content) return;

  if (editingMessageId.value) {
    await updateMessage(editingMessageId.value, content);
  } else {
    await sendMessage();
  }
}

 
// Envoyer message
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
 

// Modifier un message existant
async function updateMessage(message) {
  try {
 
    const res = await axios.put(`/api/messages/${message.id}`, {
      content: message.content,
      is_read: message.is_read 
    });

    // ✅ Mettre à jour le message localement
    const msgIndex = messages.value.findIndex(m => m.id === message.id);
    if (msgIndex !== -1) {
      Object.assign(messages.value[msgIndex], res.data.message)
    }

    // ✅ Réinitialiser le formulaire d’édition
    editingMessageId.value = null
    newMessage.value = ''
    scrollToBottom();
 
  } catch (err) {
    console.error("Erreur updateMessage:", err.response?.data || err.message);
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

    <div class="py-12" v-if="user">
      <div class="flex justify-center">
        <div class="w-full max-w-7xl">
          <div class="bg-white shadow-md h-[600px] flex">
            
            <!-- Sidebar utilisateurs -->
            <div class="w-1/3 border-r bg-white flex flex-col">
              <div class="p-4 border-b rounded bg-primary text-white font-semibold">
                Utilisateurs : {{ user.name }}
              </div>
              <ul>
                <li
                  v-for="u in otherUsersWithUnread"
                  :key="u.id"
                  @click="selectUser(u.id)"
                  class="hover:bg-primary-only hover:text-primary-txt hover:font-semibold hover:border hover:text-white p-2 rounded flex items-center justify-between cursor-pointer"
                  :class="activeUserClass(u.id)"
                >
                  <!-- Avatar + Nom -->
                  <div class="flex items-center">
                    <div class="w-8 h-8 flex-shrink-0 flex items-center justify-center mr-3 rounded-full bg-primary text-white font-bold">
                      {{ u.name.split(' ').map(n => n[0]).join('').toUpperCase() }}
                    </div>
                    <span>{{ u.name }}</span>

                    <!-- Badge rouge pour nombre -->
                    <span v-if="u.unread > 0" class="bg-green-500 text-white text-xs font-bold px-2 py-0.5 rounded-full ml-2">
                      {{ u.unread }}
                    </span>

                  </div> 
                </li>
              </ul> 
            </div> 
            <!-- Zone discussion -->
            <div class="w-2/3 flex flex-col">
              <!-- Header discussion -->
              <div class="p-4 border-b bg-primary text-white font-semibold">
                <span v-if="receiverUser">Discussion avec {{ receiverUser.name }}</span>
                <span v-else>Sélectionnez un utilisateur</span>
              </div> 
              <!-- Liste messages -->
              <div class=" messages-container flex-1 p-4 overflow-y-auto flex flex-col space-y-2  bg-gray-50"
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
                        : 'bg-gray-200 text-gray-800'
                      ]" 
                    > 
                    <p class="text-sm">{{ msg.content }}</p> 


                    <!-- ✅ Indicateur “lu” pour les messages envoyés --> 
                     <div class="w-full flex justify-between items-end mt-1">
                      <span class="text-xs opacity-70  ">
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
                      <!-- Trois points -->
                      <span 
                        @click="toggleMenu(msg.id)" 
                        class="absolute top-0 right-0 cursor-pointer text-black font-bold hover:text-primary-txt
                        w-6 h-6 flex items-center justify-center rounded-full hover:bg-primary-only transition-colors duration-200"
                        
                        title="Options"
                      >
                        ⋮
                      </span> 
                      <!-- Menu contextuel -->
                        <div 
                          v-if="activeMenuId === msg.id" 
                          class="absolute top-1 right-10 bg-white p-2 shadow-lg border rounded-lg z-20 w-32 
                                animate-fade-in"
                        >
                          <ul class="text-sm">
                            <li 
                              @click="deleteMessage(msg.id)" 
                              class="px-3 py-2 hover:bg-red-100 cursor-pointer border-b border-primary-only text-red-500 flex items-center gap-2 rounded-md transition"
                            >
                              🗑Supprimer
                            </li>
                            <li 
                              @click="editMessage(msg)" 
                              class="px-3 py-2 hover:bg-green-100 cursor-pointer text-green-600 flex items-center gap-2 rounded-md transition"
                            >
                              ✏️Modifier
                            </li>
                          </ul>
                        </div> 
                    </template> 
                  </div>
                </div> 
              </div> 
              <!-- Formulaire -->
              <form v-if="receiverId" @submit.prevent="handleSubmit" class="p-4 border-primary-only flex space-x-2">
                <input 
                  v-model="newMessage" 
                  type="text" 
                  class="flex-1 border border-primary-only rounded px-3 py-2"
                  placeholder="Écrire un message..."
                 />
                <button 
                  type="submit" 
                  class="bg-primary text-white px-3 py-2 rounded"
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
.animate-fade-in {
  animation: fadeIn 0.15s ease-out;
}
</style>

