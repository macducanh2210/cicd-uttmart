class GeminiChatWidget {
    constructor(config = {}) {
        this.apiKey = config.apiKey || 'AIzaSyDv2lbOGewJ0exC4g1IHZTNzR3GQrI8QMc';
        // Danh sách model fallback theo ưu tiên
        // gemini-2.5-flash là model có quota trong project này (5 RPM / 20 RPD)
        // gemini-2.0-* có quota = 0 nên không dùng được
        this.models = [
            { name: 'gemini-2.5-flash-preview-05-20', ver: 'v1beta' },
            { name: 'gemini-2.5-flash',               ver: 'v1beta' },
            { name: 'gemini-1.5-flash',               ver: 'v1'     },
            { name: 'gemini-1.5-flash-001',           ver: 'v1'     }
        ];
        this.currentModelIndex = 0;
        this.conversationHistory = []; // Lưu lịch sử hội thoại cho context
        this.isStaff = config.isStaff || false;
        this.debugMode = config.debugMode || false; // Enable for testing without API calls
        this.userName = config.userName || 'Customer';
        this.userRole = config.userRole || 'customer';

        this.init();
    }

    init() {
        this.createWidget();
        this.bindEvents();
        this.loadChatHistory();
    }

    createWidget() {
        // Create chat widget HTML
        const widgetHTML = `
            <div id="gemini-chat-widget" class="chat-widget">
                <div class="chat-toggle" id="chat-toggle">
                    <i class="fa-solid fa-comments"></i>
                    <span class="chat-notification" id="chat-notification" style="display: none;"></span>
                </div>
                <div class="chat-window" id="chat-window" style="display: none;">
                    <div class="chat-header">
                        <div class="chat-header-info">
                            <i class="fa-solid fa-robot"></i>
                            <span>AI Assistant</span>
                        </div>
                        <button class="chat-close" id="chat-close">
                            <i class="fa-solid fa-times"></i>
                        </button>
                    </div>
                    <div class="chat-messages" id="chat-messages">
                        <div class="message bot-message">
                            <div class="message-avatar">
                                <i class="fa-solid fa-robot"></i>
                            </div>
                            <div class="message-content">
                                <div class="message-text">
                                    ${this.isStaff ?
                'Xin chào! Tôi là trợ lý AI của UTTMart. Tôi có thể giúp bạn trả lời câu hỏi về quản lý cửa hàng, đơn hàng, sản phẩm và khách hàng.' :
                'Xin chào! Tôi là trợ lý AI của UTTMart. Tôi có thể giúp bạn tìm hiểu về sản phẩm, đơn hàng và chính sách mua hàng.'
            }
                                </div>
                                <div class="message-time">${this.getCurrentTime()}</div>
                            </div>
                        </div>
                    </div>
                    <div class="chat-input-area">
                        <div class="chat-input-container">
                            <input type="text" id="chat-input" placeholder="Nhập câu hỏi của bạn..." maxlength="500">
                            <button id="chat-send" class="chat-send-btn">
                                <i class="fa-solid fa-paper-plane"></i>
                            </button>
                        </div>
                        <div class="chat-typing" id="chat-typing" style="display: none;">
                            <div class="typing-indicator">
                                <span></span>
                                <span></span>
                                <span></span>
                            </div>
                            <span>AI đang trả lời...</span>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Create styles
        const styles = `
            <style>
                .chat-widget {
                    position: fixed;
                    bottom: 20px;
                    right: 20px;
                    z-index: 1000;
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                }

                .chat-toggle {
                    width: 60px;
                    height: 60px;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    cursor: pointer;
                    box-shadow: 0 4px 20px rgba(102, 126, 234, 0.4);
                    transition: all 0.3s ease;
                    position: relative;
                }

                .chat-toggle:hover {
                    transform: scale(1.05);
                    box-shadow: 0 6px 25px rgba(102, 126, 234, 0.6);
                }

                .chat-toggle i {
                    color: white;
                    font-size: 24px;
                }

                .chat-notification {
                    position: absolute;
                    top: -5px;
                    right: -5px;
                    width: 20px;
                    height: 20px;
                    background: #ff4757;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 12px;
                    color: white;
                    font-weight: bold;
                }

                .chat-window {
                    position: absolute;
                    bottom: 80px;
                    right: 0;
                    width: 350px;
                    height: 500px;
                    background: white;
                    border-radius: 20px;
                    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
                    display: flex;
                    flex-direction: column;
                    overflow: hidden;
                    animation: slideUp 0.3s ease;
                }

                @keyframes slideUp {
                    from { transform: translateY(20px); opacity: 0; }
                    to { transform: translateY(0); opacity: 1; }
                }

                .chat-header {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 15px 20px;
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                }

                .chat-header-info {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }

                .chat-header-info i {
                    font-size: 20px;
                }

                .chat-header-info span {
                    font-weight: 600;
                }

                .chat-close {
                    background: none;
                    border: none;
                    color: white;
                    cursor: pointer;
                    padding: 5px;
                    border-radius: 50%;
                    transition: background 0.2s ease;
                }

                .chat-close:hover {
                    background: rgba(255, 255, 255, 0.2);
                }

                .chat-messages {
                    flex: 1;
                    padding: 15px;
                    overflow-y: auto;
                    background: #f8f9fa;
                }

                .message {
                    display: flex;
                    margin-bottom: 15px;
                    animation: fadeIn 0.3s ease;
                }

                @keyframes fadeIn {
                    from { opacity: 0; transform: translateY(10px); }
                    to { opacity: 1; transform: translateY(0); }
                }

                .message-avatar {
                    width: 36px;
                    height: 36px;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin-right: 10px;
                    flex-shrink: 0;
                }

                .bot-message .message-avatar {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                }

                .user-message .message-avatar {
                    background: #e9ecef;
                    color: #6c757d;
                }

                .user-message {
                    flex-direction: row-reverse;
                }

                .user-message .message-content {
                    align-items: flex-end;
                }

                .message-content {
                    flex: 1;
                    display: flex;
                    flex-direction: column;
                }

                .message-text {
                    background: white;
                    padding: 10px 15px;
                    border-radius: 18px;
                    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
                    max-width: 250px;
                    word-wrap: break-word;
                }

                .user-message .message-text {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                }

                .message-time {
                    font-size: 11px;
                    color: #8e9297;
                    margin-top: 4px;
                    padding: 0 5px;
                }

                .chat-input-area {
                    padding: 15px;
                    background: white;
                    border-top: 1px solid #e9ecef;
                }

                .chat-input-container {
                    display: flex;
                    gap: 10px;
                    align-items: center;
                }

                #chat-input {
                    flex: 1;
                    border: 2px solid #e9ecef;
                    border-radius: 25px;
                    padding: 10px 15px;
                    outline: none;
                    font-size: 14px;
                    transition: border-color 0.2s ease;
                }

                #chat-input:focus {
                    border-color: #667eea;
                }

                .chat-send-btn {
                    width: 40px;
                    height: 40px;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    border: none;
                    border-radius: 50%;
                    color: white;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    transition: all 0.2s ease;
                }

                .chat-send-btn:hover {
                    transform: scale(1.05);
                }

                .chat-send-btn:disabled {
                    opacity: 0.6;
                    cursor: not-allowed;
                    transform: none;
                }

                .chat-typing {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    padding: 10px 0;
                    color: #6c757d;
                    font-size: 14px;
                }

                .typing-indicator {
                    display: flex;
                    gap: 4px;
                }

                .typing-indicator span {
                    width: 6px;
                    height: 6px;
                    background: #667eea;
                    border-radius: 50%;
                    animation: typing 1.4s infinite;
                }

                .typing-indicator span:nth-child(2) {
                    animation-delay: 0.2s;
                }

                .typing-indicator span:nth-child(3) {
                    animation-delay: 0.4s;
                }

                @keyframes typing {
                    0%, 60%, 100% {
                        transform: translateY(0);
                    }
                    30% {
                        transform: translateY(-10px);
                    }
                }

                @media (max-width: 480px) {
                    .chat-window {
                        width: calc(100vw - 40px);
                        height: calc(100vh - 120px);
                        bottom: 80px;
                        right: 20px;
                    }
                }
            </style>
        `;

        // Inject styles and HTML
        document.head.insertAdjacentHTML('beforeend', styles);
        document.body.insertAdjacentHTML('beforeend', widgetHTML);

        // Get DOM elements
        this.widget = document.getElementById('gemini-chat-widget');
        this.toggle = document.getElementById('chat-toggle');
        this.window = document.getElementById('chat-window');
        this.closeBtn = document.getElementById('chat-close');
        this.messages = document.getElementById('chat-messages');
        this.input = document.getElementById('chat-input');
        this.sendBtn = document.getElementById('chat-send');
        this.typing = document.getElementById('chat-typing');
        this.notification = document.getElementById('chat-notification');
    }

    bindEvents() {
        this.toggle.addEventListener('click', () => this.toggleChat());
        this.closeBtn.addEventListener('click', () => this.closeChat());
        this.sendBtn.addEventListener('click', () => this.sendMessage());
        this.input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage();
            }
        });

        // Close chat when clicking outside
        document.addEventListener('click', (e) => {
            if (!this.widget.contains(e.target) && this.window.style.display !== 'none') {
                this.closeChat();
            }
        });
    }

    toggleChat() {
        const isVisible = this.window.style.display !== 'none';
        if (isVisible) {
            this.closeChat();
        } else {
            this.openChat();
        }
    }

    openChat() {
        this.window.style.display = 'flex';
        this.input.focus();
        this.hideNotification();
        this.saveChatState();
    }

    closeChat() {
        this.window.style.display = 'none';
        this.saveChatState();
    }

    async sendMessage() {
        const message = this.input.value.trim();
        if (!message) return;

        // Add user message
        this.addMessage(message, 'user');
        this.input.value = '';

        // Show typing indicator
        this.showTyping();

        try {
            let response;
            if (this.debugMode) {
                // Mock response for testing
                response = await this.getMockResponse(message);
            } else {
                response = await this.callGeminiAPI(message);
            }
            this.hideTyping();
            this.addMessage(response, 'bot');
        } catch (error) {
            this.hideTyping();
            console.error('Gemini API error:', error);

            // Fallback sang mock response khi API lỗi hoặc hết quota
            try {
                this.showTyping();
                const mockResponse = await this.getMockResponse(message);
                this.hideTyping();
                const note = error.message === 'QUOTA_EXCEEDED'
                    ? '\n\n⚠️ *API Gemini đã hết quota hôm nay. Đây là phản hồi mẫu.*'
                    : '\n\n⚠️ *Đang dùng phản hồi mẫu vì API tạm thời không khả dụng.*';
                this.addMessage(mockResponse + note, 'bot');
                return;
            } catch (mockError) {
                this.hideTyping();
            }

            // Thông báo lỗi cuối cùng
            let errorMessage = '😔 Xin lỗi, tôi đang gặp sự cố kỹ thuật. ';
            if (error.message === 'QUOTA_EXCEEDED') {
                errorMessage += 'Quota API đã hết cho hôm nay. Vui lòng thử lại vào ngày mai hoặc liên hệ hotline 1900-xxxx.';
            } else if (error.message.includes('403')) {
                errorMessage += 'API key không có quyền truy cập. Vui lòng kiểm tra cấu hình.';
            } else {
                errorMessage += 'Vui lòng thử lại sau hoặc liên hệ hotline 1900-xxxx để được hỗ trợ.';
            }

            this.addMessage(errorMessage, 'bot');
        }
    }

    async callGeminiAPI(message) {
        const systemPrompt = this.isStaff ?
            `Bạn là trợ lý AI chuyên nghiệp của UTTMart - hệ thống quản lý bán lẻ hiện đại.
Vai trò: Hỗ trợ nhân viên & quản trị viên vận hành cửa hàng hiệu quả.

Kiến thức chuyên sâu:
- Quản lý đơn hàng: xem, lọc, cập nhật trạng thái (pending/processing/shipped/delivered/cancelled)
- Quản lý sản phẩm: thêm/sửa/xóa, quản lý tồn kho, nhập hàng từ nhà cung cấp
- Quản lý khách hàng: xem hồ sơ, lịch sử mua hàng, điểm tích lũy
- Báo cáo & thống kê: doanh thu theo ngày/tháng/năm, sản phẩm bán chạy
- Quản lý nhân viên: ca làm việc, chấm công, lương thưởng
- Thanh toán: hỗ trợ COD, chuyển khoản, MoMo, QR Pay
- Khuyến mãi: tạo mã giảm giá, flash sale, combo

Quy tắc trả lời:
1. Ngắn gọn, súc tích, đúng trọng tâm
2. Dùng bullet points khi liệt kê
3. Ưu tiên giải pháp thực tế, có thể áp dụng ngay
4. Nếu không chắc, hãy nói rõ và gợi ý liên hệ bộ phận phù hợp
5. Luôn trả lời bằng tiếng Việt trừ khi được yêu cầu khác
Người dùng hiện tại: ${this.userName} (${this.userRole})` :

            `Bạn là trợ lý AI thân thiện của UTTMart - cửa hàng bán lẻ uy tín.
Vai trò: Hỗ trợ khách hàng mua sắm dễ dàng và vui vẻ.

Thông tin UTTMart:
- Chuyên cung cấp hàng tiêu dùng, thực phẩm, gia dụng, thời trang
- Giao hàng toàn quốc: nội thành 1-2 ngày, ngoại tỉnh 3-5 ngày
- Miễn phí ship cho đơn từ 300.000đ
- Đổi trả trong 7 ngày, hoàn tiền 100% nếu lỗi từ cửa hàng
- Thanh toán: COD, chuyển khoản, MoMo, thẻ ngân hàng
- Hotline hỗ trợ: 1900-xxxx (8h-22h)

Quy tắc trả lời:
1. Thân thiện, nhiệt tình, dễ hiểu
2. Trả lời đúng câu hỏi, không lan man
3. Gợi ý thêm sản phẩm/dịch vụ liên quan khi phù hợp
4. Nếu cần tra cứu đơn hàng cụ thể, hướng dẫn khách vào trang "Đơn hàng của tôi"
5. Luôn kết thúc bằng lời mời hỗ trợ thêm
Khách hàng: ${this.userName}`;

        // Thêm tin nhắn mới vào lịch sử
        this.conversationHistory.push({
            role: 'user',
            parts: [{ text: message }]
        });

        // Giới hạn lịch sử 10 tin nhắn gần nhất
        if (this.conversationHistory.length > 10) {
            this.conversationHistory = this.conversationHistory.slice(-10);
        }

        const genConfig = {
            temperature: 0.75,
            topK: 40,
            topP: 0.95,
            maxOutputTokens: 800,
        };
        const safety = [
            { category: 'HARM_CATEGORY_HARASSMENT', threshold: 'BLOCK_ONLY_HIGH' },
            { category: 'HARM_CATEGORY_HATE_SPEECH', threshold: 'BLOCK_ONLY_HIGH' }
        ];

        // Thử lần lượt từng model nếu quota hết
        for (let i = this.currentModelIndex; i < this.models.length; i++) {
            const { name, ver } = this.models[i];
            const modelUrl = `https://generativelanguage.googleapis.com/${ver}/models/${name}:generateContent`;

            // v1beta hỗ trợ system_instruction; v1 thì nhúng prompt vào đầu contents
            let requestBody;
            if (ver === 'v1beta') {
                requestBody = {
                    system_instruction: { parts: [{ text: systemPrompt }] },
                    contents: this.conversationHistory,
                    generationConfig: genConfig,
                    safetySettings: safety
                };
            } else {
                // v1: nhúng system prompt vào tin nhắn đầu tiên
                const contentsWithSystem = [
                    { role: 'user', parts: [{ text: `[Hướng dẫn]: ${systemPrompt}` }] },
                    { role: 'model', parts: [{ text: 'Đã hiểu, tôi sẽ tuân theo hướng dẫn trên.' }] },
                    ...this.conversationHistory
                ];
                requestBody = {
                    contents: contentsWithSystem,
                    generationConfig: genConfig,
                    safetySettings: safety
                };
            }

            const response = await fetch(`${modelUrl}?key=${this.apiKey}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(requestBody)
            });

            if (response.status === 429 || response.status === 404 || response.status === 400) {
                const detail = await response.json().catch(() => ({}));
                console.warn(`[UTTMart AI] Model ${name} không khả dụng (${response.status}: ${detail?.error?.message?.slice(0,60) || ''}), thử tiếp...`);
                this.currentModelIndex = i + 1;
                continue;
            }

            if (!response.ok) {
                const errData = await response.json().catch(() => ({}));
                throw new Error(`API request failed: ${response.status} - ${errData?.error?.message || ''}`);
            }

            const data = await response.json();

            if (data.candidates && data.candidates[0] && data.candidates[0].content) {
                const replyText = data.candidates[0].content.parts[0].text;
                this.conversationHistory.push({
                    role: 'model',
                    parts: [{ text: replyText }]
                });
                return replyText;
            } else {
                throw new Error('Invalid API response');
            }
        }

        // Tất cả model đều không khả dụng
        throw new Error('QUOTA_EXCEEDED');
    }

    async getMockResponse(message) {
        // Simulate API delay
        await new Promise(resolve => setTimeout(resolve, 1000 + Math.random() * 2000));

        const lowerMessage = message.toLowerCase();

        if (this.isStaff) {
            // Mock responses for staff
            if (lowerMessage.includes('đơn hàng') || lowerMessage.includes('order')) {
                return 'Để quản lý đơn hàng, bạn có thể:\n\n1. Xem danh sách đơn hàng trong trang Orders\n2. Lọc theo trạng thái (đang xử lý, đã giao, đã hủy)\n3. Cập nhật trạng thái đơn hàng\n4. Xuất báo cáo doanh thu\n\nBạn cần hỗ trợ gì cụ thể hơn không?';
            } else if (lowerMessage.includes('sản phẩm') || lowerMessage.includes('product')) {
                return 'Quản lý sản phẩm bao gồm:\n\n• Thêm/sửa/xóa sản phẩm trong trang Products\n• Quản lý tồn kho và nhập hàng\n• Cập nhật giá cả và thông tin sản phẩm\n• Theo dõi sản phẩm bán chạy\n\nTôi có thể giúp bạn với việc nào?';
            } else if (lowerMessage.includes('khách hàng') || lowerMessage.includes('customer')) {
                return 'Để chăm sóc khách hàng:\n\n• Xem thông tin khách hàng trong hệ thống\n• Theo dõi lịch sử mua hàng\n• Xử lý khiếu nại và đổi trả\n• Gửi thông báo khuyến mãi\n\nBạn muốn biết thêm về khía cạnh nào?';
            } else {
                return 'Tôi là trợ lý AI cho nhân viên UTTMart. Tôi có thể giúp bạn với:\n\n• Quản lý đơn hàng và khách hàng\n• Báo cáo doanh thu và thống kê\n• Hướng dẫn sử dụng hệ thống\n• Tư vấn chiến lược kinh doanh\n\nBạn có câu hỏi gì cụ thể không?';
            }
        } else {
            // Mock responses for customers
            if (lowerMessage.includes('đặt hàng') || lowerMessage.includes('mua')) {
                return 'Để đặt hàng tại UTTMart:\n\n1. Chọn sản phẩm bạn muốn mua\n2. Thêm vào giỏ hàng\n3. Điền thông tin giao hàng\n4. Chọn phương thức thanh toán\n5. Xác nhận đơn hàng\n\nChúng tôi hỗ trợ thanh toán khi nhận hàng và chuyển khoản. Giao hàng trong 2-3 ngày trên toàn quốc!';
            } else if (lowerMessage.includes('đổi trả') || lowerMessage.includes('return')) {
                return 'Chính sách đổi trả UTTMart:\n\n• Đổi trả trong 7 ngày kể từ ngày nhận hàng\n• Sản phẩm còn nguyên tem mác, chưa sử dụng\n• Hoàn tiền 100% hoặc đổi sang sản phẩm khác\n• Phí ship đổi trả do UTTMart chịu\n\nLiên hệ hotline 1900-xxxx để được hỗ trợ!';
            } else if (lowerMessage.includes('ship') || lowerMessage.includes('giao hàng')) {
                return 'Thông tin giao hàng:\n\n• Miễn phí ship cho đơn hàng từ 300k\n• Giao hàng 2-3 ngày trong nội thành\n• 3-5 ngày cho ngoại tỉnh\n• Theo dõi đơn hàng qua mã vận đơn\n\nBạn muốn kiểm tra đơn hàng cụ thể nào?';
            } else {
                return 'Xin chào! Tôi là trợ lý AI của UTTMart. Tôi có thể giúp bạn:\n\n• Tư vấn chọn size và màu sắc phù hợp\n• Hướng dẫn đặt hàng và thanh toán\n• Thông tin về chính sách đổi trả\n• Theo dõi tình trạng đơn hàng\n\nBạn cần hỗ trợ gì hôm nay?';
            }
        }
    }

    addMessage(text, type) {
        const messageHTML = `
            <div class="message ${type}-message">
                <div class="message-avatar">
                    <i class="fa-solid ${type === 'bot' ? 'fa-robot' : 'fa-user'}"></i>
                </div>
                <div class="message-content">
                    <div class="message-text">${this.formatMessage(text)}</div>
                    <div class="message-time">${this.getCurrentTime()}</div>
                </div>
            </div>
        `;

        this.messages.insertAdjacentHTML('beforeend', messageHTML);
        this.scrollToBottom();
        this.saveChatHistory();
    }

    formatMessage(text) {
        return text
            // Bold: **text**
            .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
            // Italic: *text*
            .replace(/\*([^*\n]+?)\*/g, '<em>$1</em>')
            // Bullet points: - item hoặc • item
            .replace(/^[\-•]\s+(.+)/gm, '<li>$1</li>')
            .replace(/(<li>.*<\/li>)/s, '<ul style="margin:6px 0 6px 16px;padding:0">$1</ul>')
            // Numbered list: 1. item
            .replace(/^\d+\.\s+(.+)/gm, '<li>$1</li>')
            // Line breaks
            .replace(/\n/g, '<br>')
            // Links
            .replace(/(https?:\/\/[^\s<]+)/g, '<a href="$1" target="_blank" rel="noopener" style="color:#667eea">$1</a>');
    }

    showTyping() {
        this.typing.style.display = 'flex';
        this.sendBtn.disabled = true;
    }

    hideTyping() {
        this.typing.style.display = 'none';
        this.sendBtn.disabled = false;
    }

    showNotification(count = 1) {
        this.notification.textContent = count > 9 ? '9+' : count;
        this.notification.style.display = 'flex';
    }

    hideNotification() {
        this.notification.style.display = 'none';
    }

    scrollToBottom() {
        setTimeout(() => {
            this.messages.scrollTop = this.messages.scrollHeight;
        }, 100);
    }

    getCurrentTime() {
        return new Date().toLocaleTimeString('vi-VN', {
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    saveChatHistory() {
        const messages = Array.from(this.messages.children).map(msg => ({
            type: msg.classList.contains('bot-message') ? 'bot' : 'user',
            text: msg.querySelector('.message-text').innerHTML,
            time: msg.querySelector('.message-time').textContent
        }));

        const key = this.isStaff ? 'gemini_chat_staff' : 'gemini_chat_customer';
        localStorage.setItem(key, JSON.stringify(messages));
    }

    loadChatHistory() {
        const key = this.isStaff ? 'gemini_chat_staff' : 'gemini_chat_customer';
        const history = localStorage.getItem(key);

        if (history) {
            try {
                const messages = JSON.parse(history);
                messages.forEach(msg => {
                    const messageHTML = `
                        <div class="message ${msg.type}-message">
                            <div class="message-avatar">
                                <i class="fa-solid ${msg.type === 'bot' ? 'fa-robot' : 'fa-user'}"></i>
                            </div>
                            <div class="message-content">
                                <div class="message-text">${msg.text}</div>
                                <div class="message-time">${msg.time}</div>
                            </div>
                        </div>
                    `;
                    this.messages.insertAdjacentHTML('beforeend', messageHTML);
                });
                this.scrollToBottom();
            } catch (e) {
                console.error('Error loading chat history:', e);
            }
        }
    }

    saveChatState() {
        const key = this.isStaff ? 'gemini_chat_state_staff' : 'gemini_chat_state_customer';
        const state = {
            isOpen: this.window.style.display !== 'none'
        };
        localStorage.setItem(key, JSON.stringify(state));
    }

    loadChatState() {
        const key = this.isStaff ? 'gemini_chat_state_staff' : 'gemini_chat_state_customer';
        const state = localStorage.getItem(key);

        if (state) {
            try {
                const parsed = JSON.parse(state);
                if (parsed.isOpen) {
                    this.openChat();
                }
            } catch (e) {
                console.error('Error loading chat state:', e);
            }
        }
    }

    // Public method to programmatically open chat
    open() {
        this.openChat();
    }

    // Public method to programmatically close chat
    close() {
        this.closeChat();
    }

    // Public method to show notification
    notify() {
        this.showNotification();
    }
}

// Auto-initialize based on current page
document.addEventListener('DOMContentLoaded', function () {
    // Check if we're on staff/admin page
    const isStaffPage = window.location.pathname.includes('manage.html') ||
        window.location.pathname.includes('admin-') ||
        window.location.pathname.includes('employees.html') ||
        window.location.pathname.includes('suppliers.html') ||
        window.location.pathname.includes('attendance.html') ||
        window.location.pathname.includes('expenses.html');

    // Get user info from localStorage
    const userData = localStorage.getItem('polymart_user');
    let userName = 'Customer';
    let userRole = 'customer';
    let isStaff = false;

    if (userData) {
        try {
            const user = JSON.parse(userData);
            userName = user.HOTEN || user.name || 'User';
            userRole = (user.ROLE || '').toLowerCase();
            isStaff = userRole === 'admin' || userRole === 'staff' || isStaffPage;
        } catch (e) {
            console.error('Error parsing user data:', e);
        }
    }

    // Initialize chat widget
    window.geminiChat = new GeminiChatWidget({
        isStaff: isStaff,
        userName: userName,
        userRole: userRole,
        debugMode: false // Dùng Gemini API thực - set true để test với mock responses
    });
});