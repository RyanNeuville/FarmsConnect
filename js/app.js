window.fermeApp = function () {
  return {
    currentPage: "login", // login, dashboard, alerts, detail, settings
    isOnline: navigator.onLine,
    lastSyncTime: new Date().toLocaleTimeString("fr-FR", {
      hour: "2-digit",
      minute: "2-digit",
    }),
    toastMessage: "",
    user: { name: "Jean" },

    settings: {
      maxTemp: 35,
      minWater: 20,
      notifications: true,
    },

    sensors: {
      temp: {
        id: "temp",
        name: "Serre Primaire",
        val: 23,
        unit: "°C",
        icon: "thermometer",
        color: "text-ferme",
        bg: "bg-emerald-100",
        history: [21, 22, 21, 24, 25, 24, 23],
      },
      humidity: {
        id: "humidity",
        name: "Humidité Sol",
        val: 68,
        unit: "%",
        icon: "droplets",
        color: "text-blue-500",
        bg: "bg-blue-100",
        history: [50, 55, 60, 65, 67, 70, 68],
      },
      water: {
        id: "water",
        name: "Réservoir Eau",
        val: 78,
        unit: "%",
        icon: "container",
        color: "text-cyan-600",
        bg: "bg-cyan-100",
        history: [90, 85, 80, 78, 75, 76, 78],
      },
      battery: {
        id: "battery",
        name: "Batt. Météo",
        val: 92,
        unit: "%",
        icon: "battery-medium",
        color: "text-orange-500",
        bg: "bg-orange-100",
        history: [100, 98, 96, 95, 94, 93, 92],
      },
    },
    selectedSensor: null,

    actuators: {
      pump: false,
    },

    alerts: [
      {
        id: 1,
        type: "critique",
        title: "Niveau d'eau bas",
        desc: "Le réservoir est en dessous de 20%.",
        time: "Il y a 10 min",
        read: false,
        action: "pump_off",
      },
      {
        id: 2,
        type: "warning",
        title: "Température élevée",
        desc: "La Serre a dépassé 30°C.",
        time: "08:15",
        read: false,
        action: "none",
      },
    ],

    simInterval: null,

    initApp() {
      // Restore session/state
      const saved = localStorage.getItem("farmSettings");
      if (saved) this.settings = JSON.parse(saved);

      // Network listeners
      window.addEventListener("online", () => {
        this.isOnline = true;
        this.showToast("🚀 Réseau rétabli, synchro...");
        this.lastSyncTime = new Date().toLocaleTimeString("fr-FR", {
          hour: "2-digit",
          minute: "2-digit",
        });
      });
      window.addEventListener("offline", () => {
        this.isOnline = false;
        this.showToast("⚠️ Connexion perdue. Mode hors-ligne.");
      });

      // Register Service Worker
      if ("serviceWorker" in navigator) {
        navigator.serviceWorker
          .register("sw.js")
          .catch((err) => console.error(err));
      }
    },

    login() {
      this.currentPage = "dashboard";
      this.startSimulation();
    },

    logout() {
      this.currentPage = "login";
      clearInterval(this.simInterval);
      this.toastMessage = "";
    },

    getPageTitle() {
      const t = {
        dashboard: "Vue Générale",
        alerts: "Alertes Récentes",
        detail: "Détail Capteur",
        settings: "Paramètres",
      };
      return t[this.currentPage] || "";
    },

    viewSensor(sensorKey) {
      this.selectedSensor = this.sensors[sensorKey];
      this.currentPage = "detail";
    },

    togglePump() {
      this.actuators.pump = !this.actuators.pump;
      if (this.isOnline) {
        this.showToast(
          this.actuators.pump ? "💧 Pompe activée" : "🛑 Pompe arrêtée",
        );
      } else {
        this.showToast("⏳ Commande stockée hors-ligne");
      }
    },

    markAlertRead(id) {
      const alerte = this.alerts.find((a) => a.id === id);
      if (alerte) alerte.read = true;
    },

    get unreadAlertsCount() {
      return this.alerts.filter((a) => !a.read).length;
    },

    saveSettings() {
      localStorage.setItem("farmSettings", JSON.stringify(this.settings));
      this.showToast("✅ Réglages sauvegardés");
    },

    showToast(msg) {
      this.toastMessage = msg;
      setTimeout(() => {
        if (this.toastMessage === msg) this.toastMessage = "";
      }, 3500);
    },

    startSimulation() {
      this.simInterval = setInterval(() => {
        // Simulate sensor changes
        const tempDiff = Math.random() > 0.5 ? 0.5 : -0.5;
        this.sensors.temp.val = parseFloat(
          Math.max(10, Math.min(45, this.sensors.temp.val + tempDiff)).toFixed(
            1,
          ),
        );

        if (this.actuators.pump) {
          this.sensors.water.val = Math.max(0, this.sensors.water.val - 0.5);
        }

        // Auto-trigger alerts
        if (
          this.sensors.temp.val >= this.settings.maxTemp &&
          !this.alerts.some((a) => !a.read && a.id === "auto_temp")
        ) {
          this.alerts.unshift({
            id: "auto_temp",
            type: "critique",
            title: `Température critique (${this.sensors.temp.val}°C)`,
            desc: "Le capteur a détecté une surchauffe.",
            time: "À l'instant",
            read: false,
          });
          if (
            this.settings.notifications &&
            "Notification" in window &&
            Notification.permission === "granted"
          ) {
            new Notification("Ferme Connectée", {
              body: `Alerte Critique : Serre à ${this.sensors.temp.val}°C !`,
              icon: "assets/icon.svg",
            });
          }
        }
      }, 6000);
    },
  };
};
