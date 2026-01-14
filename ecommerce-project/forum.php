<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/header.php';

// Forum requires login
require_login('auth/login.php?redirect=forum.php');

$user = current_user();
$errors = [];
$success = false;

// Ensure forum_posts table exists
$conn = db();

// Create forum_posts table if it doesn't exist
$createTableSQL = 'CREATE TABLE IF NOT EXISTS forum_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    author_name VARCHAR(255) NOT NULL,
    title VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';

if (!$conn->query($createTableSQL)) {
    error_log('Failed to create forum_posts table: ' . $conn->error);
    $errors[] = 'Database error. Please contact support.';
}

// Ensure forum_replies table exists so selecting replies won't throw when missing
$createRepliesSQLTop = 'CREATE TABLE IF NOT EXISTS forum_replies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    author_name VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_post_id (post_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';

if (!$conn->query($createRepliesSQLTop)) {
    // Non-fatal: log and continue, replies will simply be empty
    error_log('Failed to ensure forum_replies table exists: ' . $conn->error);
}

// Remove foreign key constraint if it exists (we use wp5x_fc_subscribers, not users table)
// First, try to find and drop any existing foreign key constraints
$fkCheckSQL = "SELECT CONSTRAINT_NAME 
               FROM information_schema.KEY_COLUMN_USAGE 
               WHERE TABLE_SCHEMA = DATABASE() 
               AND TABLE_NAME = 'forum_posts' 
               AND REFERENCED_TABLE_NAME IS NOT NULL";
$fkResult = $conn->query($fkCheckSQL);
if ($fkResult) {
    while ($row = $fkResult->fetch_assoc()) {
        $constraintName = $row['CONSTRAINT_NAME'];
        // Try to drop the foreign key constraint
        $dropFKSQL = "ALTER TABLE forum_posts DROP FOREIGN KEY `{$constraintName}`";
        @$conn->query($dropFKSQL); // Suppress errors if constraint doesn't exist
    }
    $fkResult->free();
}

// Seed sample posts if table is empty
$countResult = $conn->query('SELECT COUNT(*) AS count FROM forum_posts');
$postCount = 0;
if ($countResult) {
    $row = $countResult->fetch_assoc();
    $postCount = (int) ($row['count'] ?? 0);
    $countResult->free();
}

if ($postCount === 0) {
    // Get subscribers from wp5x_fc_subscribers to use as authors
    $subscribersResult = $conn->query('SELECT id, first_name, last_name FROM wp5x_fc_subscribers ORDER BY id LIMIT 50');
    $subscribers = [];
    if ($subscribersResult) {
        while ($row = $subscribersResult->fetch_assoc()) {
            $subscribers[] = $row;
        }
        $subscribersResult->free();
    }

    // If we have subscribers, seed 20 sample posts
    if (count($subscribers) >= 20) {
        $samplePosts = [
            ['Amazing pottery collection from local artisans!', 'I recently purchased a handcrafted ceramic bowl and I\'m absolutely in love with it! The quality is exceptional and knowing it was made by a local artisan makes it even more special. The story behind each piece really adds value to the purchase. Highly recommend checking out the pottery section!'],
            ['Suggestion: Add filter by artisan location', 'Would it be possible to add a filter that allows us to search for artisans by their location? I\'d love to support local makers in my area and discover crafts from specific regions. This would make the browsing experience much better!'],
            ['The storytelling feature is wonderful', 'I really appreciate how each product includes the artisan\'s story and background. It makes shopping feel more personal and meaningful. Learning about the craft traditions and techniques used is fascinating. Keep up the great work!'],
            ['Shipping times could be improved', 'I love the platform, but I think shipping times could be faster. My last order took about 2 weeks to arrive. Perhaps you could work with artisans to provide estimated shipping times upfront? This would help set better expectations.'],
            ['Request: More video content of artisans at work', 'The photos are great, but I\'d love to see more video content showing artisans creating their pieces. Watching the process would be so inspiring and educational. Maybe short clips showing techniques like wheel throwing or weaving?'],
            ['Great experience with customer service', 'I had a question about a product and reached out to support. The response was quick and very helpful. The team really cares about connecting customers with the right crafts. Thank you for the excellent service!'],
            ['Wishlist feature would be helpful', 'I often find multiple items I\'d like to purchase but can\'t buy them all at once. A wishlist feature would be really useful so I can save items for later. Is this something you\'re planning to add?'],
            ['Love the paper art collection!', 'The paper art section has some beautiful pieces. I bought a handmade journal and the craftsmanship is incredible. The attention to detail is remarkable. More paper art options would be fantastic!'],
            ['Suggestion: Artisan spotlight section', 'It would be great to have a monthly or weekly spotlight on featured artisans. This could include interviews, behind-the-scenes content, and special collections. It would help us get to know the makers better!'],
            ['Mobile app would be convenient', 'The website works well on mobile, but a dedicated app would be even better. Push notifications for new products from favorite artisans, easier browsing, and quick checkout would enhance the experience significantly.'],
            ['Product quality exceeds expectations', 'I\'ve made several purchases now and every item has exceeded my expectations. The artisans clearly take pride in their work. The authenticity and quality you can\'t find in mass-produced items. This platform is doing something special!'],
            ['Could use more textile options', 'I love the textile section, but I\'d like to see more variety. Perhaps more woven items, embroidered pieces, or traditional fabric patterns? The current selection is good, but more options would be wonderful.'],
            ['Gift wrapping option needed', 'Many of these items would make perfect gifts! It would be great to have a gift wrapping option at checkout. Even a simple option would be appreciated. This could be a nice additional service.'],
            ['The forum is a great addition', 'Having this community space to share experiences and feedback is wonderful. It creates a sense of community around supporting local artisans. I\'m looking forward to more discussions here!'],
            ['Pricing seems fair for handmade items', 'I appreciate that the pricing reflects the time and skill that goes into each piece. While some items might seem expensive compared to mass-produced goods, the quality and uniqueness justify the cost. Fair trade for fair craft!'],
            ['Suggestion: Virtual artisan workshops', 'Would you consider offering virtual workshops where artisans teach their craft? This could be a great way to engage the community and provide additional income for makers. I\'d definitely sign up for pottery or weaving classes!'],
            ['Search function works well', 'The search and filter options make it easy to find what I\'m looking for. Being able to search by category, price range, and artisan is very helpful. The interface is intuitive and user-friendly.'],
            ['Woodcraft section needs more variety', 'The woodcraft items are beautiful, but I\'d love to see more diverse pieces. Perhaps kitchen utensils, decorative items, or small furniture? The current selection is nice but could be expanded.'],
            ['Excellent packaging and presentation', 'My orders always arrive beautifully packaged. The care taken in presentation shows respect for both the artisan\'s work and the customer. It makes receiving the items feel like opening a special gift!'],
            ['Overall, this is a fantastic platform!', 'I\'ve been using Heritage Craft Marketplace for a few months now and I\'m really impressed. The mission to support local artisans, the quality of products, and the overall experience is excellent. Keep up the great work! This platform is making a real difference for both artisans and customers.'],
        ];

        $insertSQL = 'INSERT INTO forum_posts (user_id, author_name, title, body, created_at) VALUES (?, ?, ?, ?, ?)';
        $stmt = $conn->prepare($insertSQL);
        
        if ($stmt) {
            for ($i = 0; $i < 20 && $i < count($samplePosts) && $i < count($subscribers); $i++) {
                $subscriber = $subscribers[$i];
                $userId = (int) $subscriber['id'];
                $authorName = trim($subscriber['first_name'] . ' ' . $subscriber['last_name']);
                $title = $samplePosts[$i][0];
                $body = $samplePosts[$i][1];
                $createdAt = date('Y-m-d H:i:s', strtotime('-' . (20 - $i) . ' days'));
                
                $stmt->bind_param('issss', $userId, $authorName, $title, $body, $createdAt);
                $stmt->execute();
            }
            $stmt->close();
        }
    }
}

// Handle new post submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Support creating a reply as well as new topics
    if (isset($_POST['form_action']) && $_POST['form_action'] === 'reply') {
        if (!validate_csrf_token('forum_reply', $_POST['csrf_token'] ?? null)) {
            $errors[] = 'Invalid form submission. Please refresh and try again.';
        } else {
            $postId = (int) ($_POST['post_id'] ?? 0);
            $body = trim($_POST['body'] ?? '');
            if ($postId <= 0) {
                $errors[] = 'Invalid post selected for reply.';
            }
            if ($body === '') {
                $errors[] = 'Reply content is required.';
            }

            if (empty($errors)) {
                // Ensure replies table exists
                $createRepliesSQL = 'CREATE TABLE IF NOT EXISTS forum_replies (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    post_id INT NOT NULL,
                    user_id INT NOT NULL,
                    author_name VARCHAR(255) NOT NULL,
                    body TEXT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_post_id (post_id),
                    INDEX idx_created_at (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
                if (!$conn->query($createRepliesSQL)) {
                    error_log('Failed to create forum_replies table: ' . $conn->error);
                    $errors[] = 'Database error. Please try again later.';
                } else {
                    $sql = 'INSERT INTO forum_replies (post_id, user_id, author_name, body) VALUES (?, ?, ?, ?)';
                    $stmt = $conn->prepare($sql);
                    if ($stmt) {
                        $uid = (int) $user['id'];
                        $authorName = $user['name'];
                        $stmt->bind_param('iiss', $postId, $uid, $authorName, $body);
                        if ($stmt->execute()) {
                            // Redirect to the same thread to show the reply
                            header('Location: ' . site_url('forum.php') . '?id=' . $postId);
                            exit;
                        } else {
                            error_log('Failed to insert forum reply: ' . $stmt->error);
                            $errors[] = 'Unable to publish your reply. Please try again.';
                        }
                        $stmt->close();
                    } else {
                        error_log('Failed to prepare statement for forum reply: ' . $conn->error);
                        $errors[] = 'Unexpected error. Please try again later.';
                    }
                }
            }
        }
    } else {
    if (!validate_csrf_token('forum_form', $_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid form submission. Please refresh and try again.';
    } else {
        $title = sanitize($_POST['title'] ?? '');
        $body = trim($_POST['body'] ?? '');

        if ($title === '') {
            $errors[] = 'Title is required.';
        }
        if ($body === '') {
            $errors[] = 'Post content is required.';
        }

        if (empty($errors)) {
            $sql = 'INSERT INTO forum_posts (user_id, author_name, title, body) VALUES (?, ?, ?, ?)';
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $uid = (int) $user['id'];
                $authorName = $user['name'];
                $stmt->bind_param('isss', $uid, $authorName, $title, $body);
                if ($stmt->execute()) {
                    $success = true;
                    // Redirect to prevent form resubmission
                    header('Location: ' . site_url('forum.php'));
                    exit;
                } else {
                    error_log('Failed to insert forum post: ' . $stmt->error);
                    $errors[] = 'Unable to publish your post. Please try again.';
                }
                $stmt->close();
            } else {
                error_log('Failed to prepare statement for forum post: ' . $conn->error);
                $errors[] = 'Unexpected error. Please try again later.';
            }
        }
    }
    }
}

// Load latest posts from database
$posts = [];
$resultPosts = $conn->query('SELECT id, author_name, title, body, created_at FROM forum_posts ORDER BY created_at DESC LIMIT 50');
if ($resultPosts) {
    while ($row = $resultPosts->fetch_assoc()) {
        $posts[] = $row;
    }
    $resultPosts->free();
} else {
    error_log('Failed to load forum posts: ' . $conn->error);
}
// If viewing a specific post, load its replies
$viewPost = null;
$replies = [];
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $viewId = (int) $_GET['id'];
    $stmt = $conn->prepare('SELECT id, user_id, author_name, title, body, created_at FROM forum_posts WHERE id = ? LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('i', $viewId);
        $stmt->execute();
        $res = $stmt->get_result();
        $viewPost = $res->fetch_assoc() ?: null;
        $stmt->close();
    }

    if ($viewPost) {
        $rstmt = $conn->prepare('SELECT id, post_id, user_id, author_name, body, created_at FROM forum_replies WHERE post_id = ? ORDER BY created_at ASC');
        if ($rstmt) {
            $rstmt->bind_param('i', $viewId);
            $rstmt->execute();
            $rres = $rstmt->get_result();
            while ($r = $rres->fetch_assoc()) {
                $replies[] = $r;
            }
            $rstmt->close();
        }
    }
    else {
        // Provide a user-facing message instead of a blank page and log DB error if present
        $errors[] = 'The requested topic was not found. It may have been removed.';
        if ($conn->error) {
            error_log('forum view error (id=' . $viewId . '): ' . $conn->error);
        }
    }
}
// Admin-only debug output to help diagnose blank pages when viewing a topic
 
?>

<section class="container page-header">
    <h1>Community Discussion Forum</h1>
    <p class="breadcrumbs"><a href="<?php echo site_url('index.php'); ?>">Home</a> / Forum</p>
</section>

<section class="container two-column">
    <div class="content-card">
        <h2>Share Your Feedback</h2>
        <p>Use this space to discuss artisan stories, suggest new features, or reflect on your experience with the Heritage Craft Marketplace. You must be signed in to post.</p>

        <?php if ($success): ?>
            <div class="alert alert-success">Your post has been published.</div>
        <?php endif; ?>

        <?php foreach ($errors as $error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endforeach; ?>

        <form method="post" class="two-column">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token('forum_form'); ?>">
            <div style="grid-column:1/-1;">
                <label for="title">Post Title</label>
                <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" required>
            </div>
            <div style="grid-column:1/-1;">
                <label for="body">Your Feedback / Opinion</label>
                <textarea name="body" id="body" rows="5" required><?php echo htmlspecialchars($_POST['body'] ?? ''); ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Publish Post</button>
        </form>
    </div>

    <aside class="content-card">
        <h2>Latest Community Posts</h2>
        <?php if (empty($posts)): ?>
            <p>No posts yet. Be the first to start a discussion!</p>
        <?php else: ?>
            <div style="display:flex; flex-direction:column; gap:1rem; max-height:520px; overflow-y:auto;">
                <?php foreach ($posts as $post): ?>
                    <article class="mini-card">
                    <h3 style="margin-bottom:0.25rem;"><a href="<?php echo site_url('forum.php') . '?id=' . (int)$post['id']; ?>"><?php echo htmlspecialchars($post['title']); ?></a></h3>
                        <p style="margin:0 0 0.5rem 0; font-size:0.9rem; color:var(--color-muted);">
                            By <?php echo htmlspecialchars($post['author_name']); ?> · <?php echo date('M j, Y', strtotime($post['created_at'])); ?>
                        </p>
                        <p style="margin:0; font-size:0.95rem;"><?php echo nl2br(htmlspecialchars(substr($post['body'], 0, 220))); ?><?php echo strlen($post['body']) > 220 ? '...' : ''; ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </aside>
</section>

<?php if ($viewPost): ?>
    <section class="container">
        <div class="content-card" style="margin-top:1rem;">
            <article>
                <h2><?php echo htmlspecialchars($viewPost['title']); ?></h2>
                <p style="margin:0 0 0.5rem 0; color:var(--color-muted);">By <?php echo htmlspecialchars($viewPost['author_name']); ?> · <?php echo date('M j, Y H:i', strtotime($viewPost['created_at'])); ?></p>
                <div style="margin:1rem 0;"><?php echo nl2br(htmlspecialchars($viewPost['body'])); ?></div>
            </article>

            <section style="margin-top:1.5rem;">
                <h3>Replies</h3>
                <?php if (empty($replies)): ?>
                    <p>No replies yet. Be the first to reply.</p>
                <?php else: ?>
                    <?php foreach ($replies as $r): ?>
                        <div class="mini-card" style="margin-bottom:0.75rem;">
                            <p style="margin:0 0 0.25rem 0;"><strong><?php echo htmlspecialchars($r['author_name']); ?></strong> · <small style="color:var(--color-muted);"><?php echo date('M j, Y H:i', strtotime($r['created_at'])); ?></small></p>
                            <div><?php echo nl2br(htmlspecialchars($r['body'])); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>

            <section style="margin-top:1.5rem;">
                <h3>Write a reply</h3>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token('forum_reply'); ?>">
                    <input type="hidden" name="form_action" value="reply">
                    <input type="hidden" name="post_id" value="<?php echo (int)$viewPost['id']; ?>">
                    <div>
                        <label for="reply_body">Your Reply</label>
                        <textarea name="body" id="reply_body" rows="4" required><?php echo htmlspecialchars($_POST['body'] ?? ''); ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary" style="margin-top:0.5rem;">Publish Reply</button>
                </form>
            </section>
        </div>
    </section>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>


