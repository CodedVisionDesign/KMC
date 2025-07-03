/**
 * Premium AI Chatbot for Krav Maga Colchester
 * Professional, responsive chatbot with modern UI/UX
 */

class KMCChatbot {
  constructor() {
    this.isOpen = false;
    this.isTyping = false;
    this.messages = [];
    // Use absolute path from domain root
    this.apiUrl = "/kmc/includes/chatbot-api.php";
    this.init();
  }

  init() {
    this.createChatbot();
    this.bindEvents();
    this.addInitialMessage();
  }

  createChatbot() {
    // Create chatbot container
    const chatbotHTML = `
            <div id="kmc-chatbot" class="kmc-chatbot">
                <!-- Floating Button -->
                <button id="kmc-chatbot-toggle" class="kmc-chatbot-toggle" aria-label="Open AI Assistant">
                    <div class="kmc-chatbot-icon">
                        <svg class="kmc-chatbot-icon-chat" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M20 2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h4l4 4 4-4h4c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-2 12H6v-2h12v2zm0-3H6V9h12v2zm0-3H6V6h12v2z"/>
                        </svg>
                        <svg class="kmc-chatbot-icon-close" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                        </svg>
                    </div>
                    <span class="kmc-chatbot-badge">AI</span>
                </button>

                <!-- Chat Window -->
                <div id="kmc-chatbot-window" class="kmc-chatbot-window">
                    <div class="kmc-chatbot-header">
                        <div class="kmc-chatbot-avatar">
                            <div class="kmc-avatar-img">KMC</div>
                            <div class="kmc-status-indicator"></div>
                        </div>
                        <div class="kmc-chatbot-info">
                            <h3>Krav Maga Assistant</h3>
                            <p><span class="kmc-ai-badge">AI</span> Here to help with your enquiries</p>
                        </div>
                        <button id="kmc-chatbot-minimize" class="kmc-chatbot-minimize" aria-label="Minimize chat">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M19 13H5v-2h14v2z"/>
                            </svg>
                        </button>
                    </div>

                    <div id="kmc-chatbot-messages" class="kmc-chatbot-messages">
                        <!-- Messages will be inserted here -->
                    </div>

                    <div class="kmc-chatbot-input-area">
                        <form id="kmc-chatbot-form" class="kmc-chatbot-form">
                            <div class="kmc-input-wrapper">
                                <input 
                                    type="text" 
                                    id="kmc-chatbot-input" 
                                    class="kmc-chatbot-input" 
                                    placeholder="Ask about our classes..."
                                    maxlength="500"
                                    autocomplete="off"
                                />
                                <button type="submit" class="kmc-chatbot-send" aria-label="Send message">
                                    <svg viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                                    </svg>
                                </button>
                            </div>
                        </form>
                        <div class="kmc-chatbot-disclaimer">
                            <small>Powered by AI • Responses may vary</small>
                        </div>
                    </div>
                </div>
            </div>
        `;

    document.body.insertAdjacentHTML("beforeend", chatbotHTML);
  }

  bindEvents() {
    const toggle = document.getElementById("kmc-chatbot-toggle");
    const minimize = document.getElementById("kmc-chatbot-minimize");
    const form = document.getElementById("kmc-chatbot-form");
    const input = document.getElementById("kmc-chatbot-input");

    toggle.addEventListener("click", () => this.toggleChat());
    minimize.addEventListener("click", () => this.closeChat());
    form.addEventListener("submit", (e) => this.handleSubmit(e));

    // Auto-resize input
    input.addEventListener("input", () => this.adjustInputHeight());

    // Close on escape key
    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape" && this.isOpen) {
        this.closeChat();
      }
    });

    // Close on outside click (mobile)
    document.addEventListener("click", (e) => {
      const chatbot = document.getElementById("kmc-chatbot");
      if (this.isOpen && !chatbot.contains(e.target)) {
        this.closeChat();
      }
    });
  }

  toggleChat() {
    if (this.isOpen) {
      this.closeChat();
    } else {
      this.openChat();
    }
  }

  openChat() {
    this.isOpen = true;
    const chatbot = document.getElementById("kmc-chatbot");
    const window = document.getElementById("kmc-chatbot-window");
    const input = document.getElementById("kmc-chatbot-input");

    chatbot.classList.add("kmc-chatbot-open");
    window.classList.add("kmc-window-open");

    // Focus input after animation
    setTimeout(() => {
      input.focus();
      this.scrollToBottom();
    }, 300);
  }

  closeChat() {
    this.isOpen = false;
    const chatbot = document.getElementById("kmc-chatbot");
    const window = document.getElementById("kmc-chatbot-window");

    chatbot.classList.remove("kmc-chatbot-open");
    window.classList.remove("kmc-window-open");
  }

  addInitialMessage() {
    const welcomeMessage =
      "Hello! I'm here to help you learn about our Krav Maga classes. Whether you're interested in adult, teen, or children's sessions, I can provide information about schedules, pricing, and help you get started with a free trial. What would you like to know?";

    this.addMessage(welcomeMessage, "bot");
  }

  async handleSubmit(e) {
    e.preventDefault();

    const input = document.getElementById("kmc-chatbot-input");
    const message = input.value.trim();

    if (!message || this.isTyping) return;

    // Add user message
    this.addMessage(message, "user");
    input.value = "";
    this.adjustInputHeight();

    // Show typing indicator
    this.showTypingIndicator();

    try {
      const response = await this.sendMessage(message);
      this.hideTypingIndicator();

      if (response.success) {
        this.addMessage(response.message, "bot");
      } else {
        this.addMessage(
          response.error || "Sorry, I encountered an error. Please try again.",
          "error"
        );
      }
    } catch (error) {
      this.hideTypingIndicator();
      this.addMessage(
        "Connection error. Please check your internet and try again.",
        "error"
      );
    }
  }

  async sendMessage(message) {
    // Add cache busting parameter
    const cacheBuster = Date.now();
    const urlWithCacheBuster = `${this.apiUrl}?cb=${cacheBuster}`;

    const response = await fetch(urlWithCacheBuster, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "Cache-Control": "no-cache",
        Pragma: "no-cache",
      },
      body: JSON.stringify({ message }),
    });

    return await response.json();
  }

  addMessage(content, type) {
    const messagesContainer = document.getElementById("kmc-chatbot-messages");
    const messageId = `msg-${Date.now()}-${Math.random()
      .toString(36)
      .substr(2, 9)}`;

    const messageHTML = `
            <div class="kmc-message kmc-message-${type}" id="${messageId}">
                ${
                  type === "bot"
                    ? '<div class="kmc-message-avatar">KMC</div>'
                    : ""
                }
                <div class="kmc-message-content">
                    <div class="kmc-message-text">${this.formatMessage(
                      content
                    )}</div>
                    ${
                      type === "bot"
                        ? '<div class="kmc-message-disclaimer">AI Response</div>'
                        : ""
                    }
                </div>
            </div>
        `;

    messagesContainer.insertAdjacentHTML("beforeend", messageHTML);
    this.scrollToBottom();

    // Animate message in
    const messageElement = document.getElementById(messageId);
    messageElement.style.opacity = "0";
    messageElement.style.transform = "translateY(20px)";

    requestAnimationFrame(() => {
      messageElement.style.transition =
        "opacity 0.3s ease, transform 0.3s ease";
      messageElement.style.opacity = "1";
      messageElement.style.transform = "translateY(0)";
    });
  }

  formatMessage(content) {
    // Basic HTML escaping and formatting
    return content
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/\n/g, "<br>")
      .replace(/\*\*(.*?)\*\*/g, "<strong>$1</strong>")
      .replace(/\*(.*?)\*/g, "<em>$1</em>");
  }

  showTypingIndicator() {
    this.isTyping = true;
    const messagesContainer = document.getElementById("kmc-chatbot-messages");

    const typingHTML = `
            <div class="kmc-message kmc-message-bot kmc-typing-indicator" id="typing-indicator">
                <div class="kmc-message-avatar">KMC</div>
                <div class="kmc-message-content">
                    <div class="kmc-typing-dots">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>
            </div>
        `;

    messagesContainer.insertAdjacentHTML("beforeend", typingHTML);
    this.scrollToBottom();
  }

  hideTypingIndicator() {
    this.isTyping = false;
    const indicator = document.getElementById("typing-indicator");
    if (indicator) {
      indicator.remove();
    }
  }

  adjustInputHeight() {
    const input = document.getElementById("kmc-chatbot-input");
    input.style.height = "auto";
    input.style.height = Math.min(input.scrollHeight, 120) + "px";
  }

  scrollToBottom() {
    const messagesContainer = document.getElementById("kmc-chatbot-messages");
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
  }
}

// Initialize chatbot when DOM is loaded
document.addEventListener("DOMContentLoaded", () => {
  new KMCChatbot();
});

// Export for potential external use
window.KMCChatbot = KMCChatbot;
