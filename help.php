<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$username = isset($_SESSION['user']['username']) ? $_SESSION['user']['username'] : 'User';
$email = isset($_SESSION['user']['email']) ? $_SESSION['user']['email'] : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KOLIJA - Help & Support</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <header class="app-header">
            <h1 class="app-title">KOLIJA</h1>
            <div class="header-buttons">
                <a href="chat.php" class="back-btn">Back to Chat</a>
                <a href="chat.php?logout=true" class="logout-btn">Logout</a>
            </div>
        </header>
        
        <main class="help-page">
            <div class="help-container">
                <h2>Help & Support</h2>
                
                <div class="help-tabs">
                    <button class="tab-btn active" data-tab="my-tickets">My Tickets</button>
                    <button class="tab-btn" data-tab="new-ticket">Create New Ticket</button>
                    <button class="tab-btn" data-tab="faqs">FAQs</button>
                </div>
                
                <div class="help-content">
                    <!-- My Tickets Tab -->
                    <div class="tab-content active" id="my-tickets-content">
                        <div id="ticketsContainer">
                            <div class="loading">Loading your tickets...</div>
                        </div>
                    </div>
                    
                    <!-- Create New Ticket Tab -->
                    <div class="tab-content" id="new-ticket-content">
                        <form id="newTicketForm" class="ticket-form">
                            <div class="form-group">
                                <label for="ticketSubject">Subject</label>
                                <input type="text" id="ticketSubject" name="subject" placeholder="Enter ticket subject" required>
                            </div>
                            <div class="form-group">
                                <label for="ticketMessage">Message</label>
                                <textarea id="ticketMessage" name="message" rows="6" placeholder="Describe your issue or question in detail..." required></textarea>
                            </div>
                            <div class="form-group">
                                <button type="submit" class="submit-btn">Submit Ticket</button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- FAQs Tab -->
                    <div class="tab-content" id="faqs-content">
                        <div class="faq-item">
                            <h3>How do I upgrade to premium?</h3>
                            <p>You can upgrade to premium by clicking on the "Upgrade" button in the chat interface. Follow the instructions to make a payment via UPI and upload your payment screenshot for verification.</p>
                        </div>
                        <div class="faq-item">
                            <h3>How many free messages do I get?</h3>
                            <p>New users get 20 free messages. After that, you'll need to upgrade to premium to continue chatting.</p>
                        </div>
                        <div class="faq-item">
                            <h3>How long does it take to verify my payment?</h3>
                            <p>Our admin typically verifies payments within 24 hours. Once verified, you'll be upgraded to premium status automatically.</p>
                        </div>
                        <div class="faq-item">
                            <h3>Can I ask any question to KOLIJA?</h3>
                            <p>KOLIJA can answer questions based on its predefined knowledge base. If it doesn't have an answer for your question, you can contact our support team for assistance.</p>
                        </div>
                        <div class="faq-item">
                            <h3>Is my data safe?</h3>
                            <p>Yes, we take data privacy seriously. Your personal information and chat history are securely stored and never shared with third parties.</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Ticket Details Modal -->
    <div id="ticketModal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h3 id="ticketModalTitle">Ticket Details</h3>
            <div id="ticketModalContent"></div>
            <div id="ticketReplyForm" class="ticket-reply-form">
                <textarea id="replyMessage" placeholder="Type your reply here..."></textarea>
                <button id="sendReplyBtn" class="submit-btn">Send Reply</button>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ticketsContainer = document.getElementById('ticketsContainer');
            const tabButtons = document.querySelectorAll('.tab-btn');
            const tabContents = document.querySelectorAll('.tab-content');
            const newTicketForm = document.getElementById('newTicketForm');
            const ticketModal = document.getElementById('ticketModal');
            const closeModal = document.querySelector('.close-modal');
            const sendReplyBtn = document.getElementById('sendReplyBtn');
            const replyMessage = document.getElementById('replyMessage');
            
            let currentTicketId = null;
            
            // Switch tabs
            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const tabId = this.getAttribute('data-tab');
                    
                    // Update active tab button
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Update active tab content
                    tabContents.forEach(content => content.classList.remove('active'));
                    document.getElementById(`${tabId}-content`).classList.add('active');
                    
                    // Load tickets when switching to My Tickets tab
                    if (tabId === 'my-tickets') {
                        loadTickets();
                    }
                });
            });
            
            // Load user's tickets
            function loadTickets() {
                ticketsContainer.innerHTML = '<div class="loading">Loading your tickets...</div>';
                
                fetch('api/help.php?action=getUserTickets')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const tickets = data.tickets || [];
                            
                            if (tickets.length > 0) {
                                ticketsContainer.innerHTML = '';
                                tickets.forEach(ticket => {
                                    const lastMessage = ticket.messages && ticket.messages.length > 0 ? 
                                        ticket.messages[ticket.messages.length - 1] : null;
                                    const truncatedMessage = lastMessage && lastMessage.message ? 
                                        (lastMessage.message.length > 100 ? 
                                            lastMessage.message.substring(0, 100) + "..." : 
                                            lastMessage.message) : 
                                        "No messages";
                                    const ticketItem = document.createElement('div');
                                    ticketItem.className = 'ticket-item';
                                    ticketItem.dataset.id = ticket.id;
                                    
                                    const statusClass = ticket.status === 'open' ? 'open' : 'closed';
                                    const statusText = ticket.status === 'open' ? 'Active' : 'Closed';
                                    
                                    ticketItem.innerHTML = `
                                        <div class="ticket-header">
                                            <h3 class="ticket-subject">${escapeHtml(ticket.subject)}</h3>
                                            <span class="ticket-status ${statusClass}">${statusText}</span>
                                        </div>
                                        <div class="ticket-preview">
                                            <span class="ticket-date">Created: ${formatDate(ticket.created_at)}</span>
                                            <p>${escapeHtml(truncatedMessage)}</p>
                                            <button class="view-ticket-btn">View Conversation</button>
                                        </div>
                                    `;
                                    
                                    ticketsContainer.appendChild(ticketItem);
                                    
                                    // Add event listener to view ticket button
                                    const viewBtn = ticketItem.querySelector('.view-ticket-btn');
                                    viewBtn.addEventListener('click', () => openTicketModal(ticket));
                                });
                            } else {
                                ticketsContainer.innerHTML = `
                                    <div class="no-tickets">
                                        <p>You haven't created any support tickets yet.</p>
                                        <button class="create-ticket-btn">Create Your First Ticket</button>
                                    </div>
                                `;
                                
                                const createBtn = ticketsContainer.querySelector('.create-ticket-btn');
                                createBtn.addEventListener('click', () => {
                                    document.querySelector('.tab-btn[data-tab="new-ticket"]').click();
                                });
                            }
                        } else {
                            ticketsContainer.innerHTML = `
                                <div class="error-message">
                                    <p>${data.message || 'Failed to load tickets. Please try again later.'}</p>
                                </div>
                            `;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        ticketsContainer.innerHTML = `
                            <div class="error-message">
                                <p>An error occurred. Please try again later.</p>
                            </div>
                        `;
                    });
            }
            
            // Open ticket modal with details
            function openTicketModal(ticket) {
                currentTicketId = ticket.id;
                
                const modalTitle = document.getElementById('ticketModalTitle');
                const modalContent = document.getElementById('ticketModalContent');
                const replyForm = document.getElementById('ticketReplyForm');
                
                modalTitle.textContent = `Ticket: ${ticket.subject}`;
                
                // Create message list HTML
                let messagesHtml = '<ul class="message-list">';
                ticket.messages && ticket.messages.forEach(message => {
                    const messageClass = message.sender === "admin" ? "admin-message" : 
                                         message.sender === "system" ? "system-message" : "user-message";
                    const senderName = (message.sender_name || 
                                      (message.sender === "admin" ? "Admin" : 
                                       message.sender === "system" ? "System" : "You"));
                    const messageContent = message.message || "";
                    messagesHtml += `
                        <li class="message ${messageClass}">
                            <div class="message-header">
                                <span class="sender">${escapeHtml(senderName)}</span>
                                <span class="timestamp">${formatDate(message.timestamp)}</span>
                            </div>
                            <div class="message-content">${escapeHtml(messageContent).replace(/\n/g, "<br>")}</div>
                        </li>
                    `;
                });
                messagesHtml += '</ul>';
                
                modalContent.innerHTML = messagesHtml;
                
                // Show/hide reply form based on ticket status
                if (ticket.status === 'closed') {
                    replyForm.style.display = 'none';
                } else {
                    replyForm.style.display = 'block';
                }
                
                // Show modal
                ticketModal.style.display = 'block';
                
                // Scroll to the bottom of messages
                const messageList = modalContent.querySelector('.message-list');
                if (messageList) {
                    messageList.scrollTop = messageList.scrollHeight;
                }
            }
            
            // Close modal when clicking on X
            if (closeModal) {
                closeModal.addEventListener('click', function() {
                    ticketModal.style.display = 'none';
                    currentTicketId = null;
                });
            }
            
            // Close modal when clicking outside of it
            window.addEventListener('click', function(event) {
                if (event.target === ticketModal) {
                    ticketModal.style.display = 'none';
                    currentTicketId = null;
                }
            });
            
            // Submit new ticket form
            if (newTicketForm) {
                newTicketForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const subject = document.getElementById('ticketSubject').value.trim();
                    const message = document.getElementById('ticketMessage').value.trim();
                    
                    if (!subject || !message) {
                        alert('Please fill in all fields');
                        return;
                    }
                    
                    // Disable form during submission
                    const submitBtn = newTicketForm.querySelector('button[type="submit"]');
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Submitting...';
                    
                    fetch('api/help.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'createTicket',
                            subject: subject,
                            message: message
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Submit Ticket';
                        
                        if (data.success) {
                            // Clear form
                            document.getElementById('ticketSubject').value = '';
                            document.getElementById('ticketMessage').value = '';
                            
                            // Show success message
                            alert('Your ticket has been submitted successfully!');
                            
                            // Switch to My Tickets tab
                            document.querySelector('.tab-btn[data-tab="my-tickets"]').click();
                        } else {
                            alert(data.message || 'Failed to submit ticket. Please try again.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Submit Ticket';
                        alert('An error occurred. Please try again later.');
                    });
                });
            }
            
            // Send reply to ticket
            if (sendReplyBtn) {
                sendReplyBtn.addEventListener('click', function() {
                    const message = replyMessage.value.trim();
                    
                    if (!message) {
                        alert('Please enter a reply message');
                        return;
                    }
                    
                    if (!currentTicketId) {
                        alert('No ticket selected');
                        return;
                    }
                    
                    // Disable button during submission
                    sendReplyBtn.disabled = true;
                    sendReplyBtn.textContent = 'Sending...';
                    
                    fetch('api/help.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'replyToTicket',
                            ticketId: currentTicketId,
                            message: message
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        sendReplyBtn.disabled = false;
                        sendReplyBtn.textContent = 'Send Reply';
                        
                        if (data.success) {
                            // Clear input
                            replyMessage.value = '';
                            
                            // Refresh ticket details
                            fetch(`api/help.php?action=getTicket&id=${currentTicketId}`)
                                .then(response => response.json())
                                .then(ticketData => {
                                    if (ticketData.success) {
                                        openTicketModal(ticketData.ticket);
                                    }
                                });
                        } else {
                            alert(data.message || 'Failed to send reply. Please try again.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        sendReplyBtn.disabled = false;
                        sendReplyBtn.textContent = 'Send Reply';
                        alert('An error occurred. Please try again later.');
                    });
                });
            }
            
            // Format date helper function
            function formatDate(dateString) {
                if (!dateString) return 'Unknown date';
                
                const date = new Date(dateString);
                
                if (isNaN(date.getTime())) {
                    return dateString; // Return as is if invalid date
                }
                
                return date.toLocaleString();
            }
            
            // Escape HTML helper function
            function escapeHtml(unsafe) {
                if (!unsafe) return '';
                
                return unsafe
                    .toString()
                    .replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;")
                    .replace(/'/g, "&#039;");
            }
            
            // Load tickets on page load
            loadTickets();
        });
    </script>
</body>
</html>
