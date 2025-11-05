<?php
/**
 * Messages Page
 * 
 * Displays user's messages and allows sending new messages
 */

// Include required files
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/database.php';

// Check if user is logged in
requireAuth();

// Get current user
$user = getCurrentUser();

// Set page title
$pageTitle = 'Messages';

// Initialize database
$db = new Database();

// Handle sending a new message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send') {
    $recipient_id = filter_input(INPUT_POST, 'recipient_id', FILTER_VALIDATE_INT);
    $subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_STRING);
    $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);
    
    try {
        // Check if there's an existing thread with this recipient
        $thread = $db->selectOne(
            "SELECT id FROM message_threads 
             WHERE (user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?)
             LIMIT 1",
            [$user['id'], $recipient_id, $recipient_id, $user['id']]
        );
        
        $thread_id = null;
        
        // Create a new thread if one doesn't exist
        if (!$thread) {
            $thread_id = $db->insert(
                "INSERT INTO message_threads (user1_id, user2_id, subject, created_at, updated_at)
                 VALUES (?, ?, ?, NOW(), NOW())",
                [$user['id'], $recipient_id, $subject]
            );
        } else {
            $thread_id = $thread['id'];
            
            // Update the thread's updated_at timestamp
            $db->execute(
                "UPDATE message_threads SET updated_at = NOW() WHERE id = ?",
                [$thread_id]
            );
        }
        
        // Insert the message
        $db->insert(
            "INSERT INTO messages (thread_id, sender_id, recipient_id, message, is_read, created_at)
             VALUES (?, ?, ?, ?, 0, NOW())",
            [$thread_id, $user['id'], $recipient_id, $message]
        );
        
        // Redirect to avoid form resubmission
        header("Location: messages.php?success=1");
        exit;
    } catch (Exception $e) {
        error_log('Error sending message: ' . $e->getMessage());
        $error = "Failed to send message. Please try again.";
    }
}

// Get message threads
try {
    $threads = $db->select(
        "SELECT t.*, 
                u1.full_name AS user1_name, u1.email AS user1_email, u1.avatar AS user1_avatar,
                u2.full_name AS user2_name, u2.email AS user2_email, u2.avatar AS user2_avatar,
                (SELECT COUNT(*) FROM messages m WHERE m.thread_id = t.id AND m.recipient_id = ? AND m.is_read = 0) AS unread_count,
                (SELECT m.created_at FROM messages m WHERE m.thread_id = t.id ORDER BY m.created_at DESC LIMIT 1) AS last_message_date,
                (SELECT m.message FROM messages m WHERE m.thread_id = t.id ORDER BY m.created_at DESC LIMIT 1) AS last_message
         FROM message_threads t
         JOIN users u1 ON t.user1_id = u1.id
         JOIN users u2 ON t.user2_id = u2.id
         WHERE t.user1_id = ? OR t.user2_id = ?
         ORDER BY t.updated_at DESC",
        [$user['id'], $user['id'], $user['id']]
    );
    
    // Get staff members for the new message form
    $staff = $db->select(
        "SELECT id, full_name, email FROM users WHERE role = 'admin' OR role = 'staff' ORDER BY full_name"
    );
    
} catch (Exception $e) {
    error_log('Messages page error: ' . $e->getMessage());
    $threads = [];
    $staff = [];
}

// Include header
include_once __DIR__ . '/../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex flex-col md:flex-row justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Messages</h1>
            <p class="text-gray-600">Communicate with our team</p>
        </div>
        
        <!-- New Message Button -->
        <div class="mt-4 md:mt-0">
            <button type="button" onclick="openNewMessageModal()" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                New Message
            </button>
        </div>
    </div>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6" role="alert">
            <strong class="font-bold">Success!</strong>
            <span class="block sm:inline"> Your message has been sent.</span>
            <button class="absolute top-0 bottom-0 right-0 px-4 py-3" onclick="this.parentNode.remove()">
                <svg class="fill-current h-6 w-6 text-green-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                    <title>Close</title>
                    <path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/>
                </svg>
            </button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
            <strong class="font-bold">Error!</strong>
            <span class="block sm:inline"> <?= htmlspecialchars($error) ?></span>
            <button class="absolute top-0 bottom-0 right-0 px-4 py-3" onclick="this.parentNode.remove()">
                <svg class="fill-current h-6 w-6 text-red-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                    <title>Close</title>
                    <path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/>
                </svg>
            </button>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($threads)): ?>
        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Contact
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Subject
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Last Message
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Date
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($threads as $thread): ?>
                            <?php
                                // Determine the other user in the conversation
                                $otherUser = $thread['user1_id'] == $user['id'] ? 
                                    [
                                        'id' => $thread['user2_id'],
                                        'name' => $thread['user2_name'],
                                        'email' => $thread['user2_email'],
                                        'avatar' => $thread['user2_avatar']
                                    ] : 
                                    [
                                        'id' => $thread['user1_id'],
                                        'name' => $thread['user1_name'],
                                        'email' => $thread['user1_email'],
                                        'avatar' => $thread['user1_avatar']
                                    ];
                            ?>
                            <tr class="<?= $thread['unread_count'] > 0 ? 'bg-blue-50' : '' ?>">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <?php if ($otherUser['avatar']): ?>
                                                <img class="h-10 w-10 rounded-full object-cover" src="<?= htmlspecialchars($otherUser['avatar']) ?>" alt="">
                                            <?php else: ?>
                                                <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                                    <span class="text-blue-800 font-medium text-sm">
                                                        <?= strtoupper(substr($otherUser['name'], 0, 1)) ?>
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?= htmlspecialchars($otherUser['name']) ?>
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                <?= htmlspecialchars($otherUser['email']) ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 font-medium">
                                        <?= htmlspecialchars($thread['subject']) ?>
                                    </div>
                                    <?php if ($thread['unread_count'] > 0): ?>
                                        <span class="inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-blue-600 rounded-full">
                                            <?= $thread['unread_count'] ?> new
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900 truncate max-w-xs">
                                        <?= htmlspecialchars(mb_substr($thread['last_message'], 0, 50)) ?>
                                        <?= mb_strlen($thread['last_message']) > 50 ? '...' : '' ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= date('M d, Y', strtotime($thread['last_message_date'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="message_thread.php?id=<?= $thread['id'] ?>" class="text-blue-600 hover:text-blue-900">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php else: ?>
        <div class="bg-gray-50 border border-gray-200 rounded-xl p-8 text-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-400 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
            <h3 class="text-xl font-medium text-gray-800 mb-2">No Messages</h3>
            <p class="text-gray-600 mb-6">You don't have any messages yet. Start a conversation with our team.</p>
            <button onclick="openNewMessageModal()" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors inline-block">
                Send a Message
            </button>
        </div>
    <?php endif; ?>
</div>

<!-- New Message Modal -->
<div id="newMessageModal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
    <div class="absolute inset-0 bg-black bg-opacity-50"></div>
    <div class="bg-white rounded-lg max-w-lg w-full mx-4 z-10">
        <div class="flex justify-between items-center border-b px-6 py-4">
            <h3 class="text-lg font-bold">New Message</h3>
            <button onclick="closeNewMessageModal()" class="text-gray-400 hover:text-gray-500">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <form action="" method="post" class="p-6">
            <input type="hidden" name="action" value="send">
            <div class="mb-4">
                <label for="recipient_id" class="block text-sm font-medium text-gray-700 mb-1">Recipient</label>
                <select id="recipient_id" name="recipient_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Select a recipient</option>
                    <?php foreach ($staff as $person): ?>
                        <option value="<?= $person['id'] ?>"><?= htmlspecialchars($person['full_name']) ?> (<?= htmlspecialchars($person['email']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-4">
                <label for="subject" class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                <input type="text" id="subject" name="subject" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="mb-6">
                <label for="message" class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                <textarea id="message" name="message" rows="4" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"></textarea>
            </div>
            <div class="flex justify-end">
                <button type="button" onclick="closeNewMessageModal()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition-colors mr-2">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    Send Message
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openNewMessageModal() {
        document.getElementById('newMessageModal').classList.remove('hidden');
    }
    
    function closeNewMessageModal() {
        document.getElementById('newMessageModal').classList.add('hidden');
    }
    
    // Close modal when clicking outside
    document.getElementById('newMessageModal').addEventListener('click', function(event) {
        if (event.target === this) {
            closeNewMessageModal();
        }
    });
</script>

<?php
// Include footer
include_once __DIR__ . '/../includes/footer.php';
?>
