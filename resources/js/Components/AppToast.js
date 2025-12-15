import { toast as vueToast } from "vue3-toastify";
import "vue3-toastify/dist/index.css";

const COLORS = {
  success: "rgba(73, 235, 181, 0.9)",   // Vert MazUI
  error: "rgba(238, 92, 92, 0.9)",      // Rouge MazUI
  info: "hsla(217, 92%, 65%, 0.9)",      // Bleu MazUI
  warning: "rgba(245, 158, 11, 0.9)",   // Orange MazUI
};

const show = (message, type = "success") => {
  vueToast(message, {
    type,
    position: "top-right",
    transition: "left",
    autoClose: 3500,    
    icon: true, 
    hideProgressBar: false, 
    closeOnClick: true,
    pauseOnHover: true,
    draggable: false,
    progress: undefined,
    theme: "light", 
    toastId: `${type}-${Date.now()}`, 
    rtl: false,
 
    style: {
      background: COLORS[type],
      color: "white",
      borderRadius: "12px",
      padding: "10px 16px",
      fontSize: "14px",
      fontWeight: "500",
      boxShadow: "0 4px 10px rgba(0, 0, 0, 0.15)",
      backdropFilter: "blur(3px)",
      minWidth: "150px",
    },
  });
};

export const toast = {
  success: msg => show(msg, "success"),
  error: msg => show(msg, "error"),
  info: msg => show(msg, "info"),
  warning: msg => show(msg, "warning"),
};
