# Psych System - Shortcode Reference Guide

This document provides a complete reference for all shortcodes available in the Psych System plugin.

---

## 1. Path & Structure Shortcodes

These shortcodes are used to build the main structure of your courses and content flows.

### `[psych_path]`
Creates the main container for a course or learning path. All `[psych_station]` shortcodes must be inside this.

| Attribute | Type | Default | Description |
|---|---|---|---|
| `path_id` | `string` | (required) | A unique ID for this path, e.g., "schema_therapy_course". |
| `path_style` | `string` | `modern-roadmap` | The visual style for the path. Options: `modern-roadmap`, `subway`, `simple-list`. |

**Example:**
```shortcode
[psych_path path_id="my_first_course" path_style="subway"]
  ... stations go here ...
[/psych_path]
```

### `[psych_station]`
Defines a chapter, module, or step within a `[psych_path]`.

| Attribute | Type | Default | Description |
|---|---|---|---|
| `station_id` | `string` | (required) | A unique ID for this station within the path, e.g., "intro". |
| `title` | `string` | "Station" | The visible title of the station. |
| `required_product_id` | `integer` | (none) | If set, the user must have purchased this WooCommerce product to access the station. |
| `required_badge` | `string` | (none) | If set, the user must have earned this badge to access the station. |

**Example:**
```shortcode
[psych_station station_id="chapter_1" title="Chapter 1: The Basics" required_product_id="123"]
  ... missions go here ...
[/psych_station]
```

### `[psych_mission]`
Defines a specific task or piece of content within a `[psych_station]`.

| Attribute | Type | Default | Description |
|---|---|---|---|
| `mission_id` | `string` | (required) | A unique ID for this mission, e.g., "watch_intro_video". |
| `title` | `string` | "Mission" | The visible title of the mission. |

**Example:**
```shortcode
[psych_mission mission_id="read_article" title="Read the Article"]
  <p>Here is the article content...</p>
[/psych_mission]
```

---

## 2. Content & Media Shortcodes

These shortcodes are used to display different types of content.

### `[psych_spot_player]`
Embeds a Spot Player video.

| Attribute | Type | Default | Description |
|---|---|---|---|
| `video_id` | `string` | (required) | The unique ID of the video provided by Spot Player. |

### `[psych_secure_audio]`
Embeds a secure audio player that prevents easy downloading.

| Attribute | Type | Default | Description |
|---|---|---|---|
| `src` | `string` | (required) | The URL to the audio file. |
| `audio_id` | `string` | (required) | A unique ID for this audio track to track completion. |

### `[psych_add_to_cart]`
Displays a WooCommerce "Add to Cart" button.

| Attribute | Type | Default | Description |
|---|---|---|---|
| `product_id` | `integer` | (required) | The ID of the WooCommerce product to add to the cart. |
| `text` | `string` | "Purchase Course" | The text displayed on the button. |

---

## 3. Interactive & Gamification Shortcodes

These shortcodes create interactive experiences for the user.

### `[psych_quiz]`
Creates an advanced quiz. Questions are nested inside. (Depends on Advanced Quiz Module).

| Attribute | Type | Default | Description |
|---|---|---|---|
| `id` | `string` | (required) | A unique ID for this quiz. |

### `[psych_ai_test_form]`
Creates a form that sends user input to an AI for analysis.

| Attribute | Type | Default | Description |
|---|---|---|---|
| `id` | `string` | "default_test" | A unique ID for this specific AI-powered test. |

### `[psych_mission_badge]`
Displays a badge that is awarded upon completion of a mission.

| Attribute | Type | Default | Description |
|---|---|---|---|
| `slug` | `string` | (required) | The unique slug for the badge to be awarded. |
| `name` | `string` | "Badge" | The name of the badge displayed to the user. |
| `icon` | `string` | "fa-star" | A Font Awesome icon class, e.g., "fa-brain". |
| `points` | `integer` | 0 | The number of points to award with the badge. |

---

## 4. Conditional & Personalized Shortcodes

These shortcodes control who sees what content.

### `[psych_personalize]`
The master shortcode for showing or hiding content based on complex conditions.

| Attribute | Type | Default | Description |
|---|---|---|---|
| `show_to` | `string` | (none) | A comma-separated list of conditions. Content is shown if ALL conditions are met. E.g., `user_has_product:123,user_has_badge:starter`. |
| `hide_from` | `string` | (none) | A comma-separated list of conditions. Content is hidden if ANY condition is met. E.g., `user_has_product:123`. |

**Available Conditions:**
*   `user_has_product:PRODUCT_ID`
*   `user_has_badge:BADGE_SLUG`
*   `user_is_coach`
*   (More can be added)

**Example:**
```shortcode
[psych_personalize show_to="user_has_product:123"]
  <p>Thank you for purchasing!</p>
[/psych_personalize]
```

---

## 5. Coach & Community Shortcodes

These shortcodes are for interactions involving coaches and other users.

### `[psych_coach_approval_gate]`
Creates a "gate" that only a coach can unlock for a student.

| Attribute | Type | Default | Description |
|---|---|---|---|
| `mission_id`| `string` | (required) | A unique ID for this specific approval gate. |

**Structure:**
```shortcode
[psych_coach_approval_gate mission_id="approval_1"]
  [message_for_student]
    <p>Waiting for your coach to approve.</p>
  [/message_for_student]
  [message_for_coach]
    <p>Click here to approve the student.</p>
    <button class="psych-approve-button">Approve</button>
  [/message_for_coach]
[/psych_coach_approval_gate]
```

### `[psych_feedback_request]`
Generates a unique link for a student to collect 360-degree feedback.

| Attribute | Type | Default | Description |
|---|---|---|---|
| `mission_id` | `string` | (required) | A unique ID for this feedback mission. |
| `required_responses` | `integer` | 3 | The number of responses required to complete the mission. |
| `form_id` | `integer` | (none) | The ID of the Gravity Form to show to people who visit the link. |

**Structure (placeholders are automatically replaced):**
```shortcode
[psych_feedback_request mission_id="feedback_1" required_responses="3"]
  [link_display]
    <p>Your link: <input value="[generated_link]"></p>
  [/link_display]
  [progress_display]
    <p>Progress: [response_count] / [required_responses]</p>
  [/progress_display]
  [completion_message]
    <p>All feedback collected!</p>
  [/completion_message]
[/psych_feedback_request]
```

### `[psych_referral_mission]`
Creates a user referral mission.

| Attribute | Type | Default | Description |
|---|---|---|---|
| `goal` | `integer` | 5 | The number of new user registrations required to complete. |
| `badge`| `string` | "ambassador"| The badge to award upon completion. |

**Structure (placeholders are automatically replaced):**
```shortcode
[psych_referral_mission goal="5"]
  <p>Your link: <input value="[referral_link]"></p>
  <p>Progress: [referral_count] / [goal]</p>
[/psych_referral_mission]
```

### `[psych_social_share]`
Displays buttons to share content on social media.

| Attribute | Type | Default | Description |
|---|---|---|---|
| `url_to_share`| `string` | Current page URL | The URL that will be shared. |
| `text_to_share`| `string` | Current page title | The text that will be pre-filled in the share. |

### `[psych_instagram_story_mission]`
A self-attested mission for sharing an image to an Instagram story.

| Attribute | Type | Default | Description |
|---|---|---|---|
| `image_url` | `string` | (required) | The URL of the image to be shared. |
| `mission_id`| `string` | "instagram_share" | A unique ID for this mission. |
| `badge` | `string` | "instagram_star" | The badge to award upon completion. |
