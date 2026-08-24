<?php
/**
 * Homepage New release row.
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('cas_home_release_enqueue_assets')) {
    function cas_home_release_enqueue_assets() {
        if (!function_exists('wp_enqueue_script')) {
            return;
        }

        if (function_exists('get_theme_file_uri')) {
            $src = get_theme_file_uri('js/cas-home-release.js');
        } elseif (function_exists('get_template_directory_uri')) {
            $src = rtrim(get_template_directory_uri(), '/') . '/js/cas-home-release.js';
        } else {
            $src = 'js/cas-home-release.js';
        }

        wp_enqueue_script('cas-home-release', $src, array(), null, true);
    }
}

if (!function_exists('cas_home_release')) {
    function cas_home_release($releases = array()) {
        cas_home_release_enqueue_assets();

        if (function_exists('apply_filters')) {
            $releases = apply_filters('cas_home_release_items', $releases);
        }
        if (empty($releases) && function_exists('get_posts')) {
            $releases = get_posts(array(
                'post_type'      => 'course',
                'post_status'    => 'publish',
                'posts_per_page' => 3,
                'orderby'        => 'date',
                'order'          => 'DESC',
            ));
        }
        if (!is_array($releases)) {
            $releases = array();
        }

        $read = static function ($item, $key, $fallback = '') {
            if (is_array($item) && array_key_exists($key, $item)) {
                return $item[$key];
            }
            if (is_object($item) && isset($item->{$key})) {
                return $item->{$key};
            }
            if (is_object($item) && isset($item->ID) && function_exists('get_post_meta')) {
                $value = get_post_meta($item->ID, $key, true);
                if ($value !== '' && $value !== null) {
                    return $value;
                }
            }
            return $fallback;
        };
        $html = static function ($value) {
            $value = (string) $value;
            return function_exists('esc_html')
                ? esc_html($value)
                : htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        };
        $attr = static function ($value) {
            $value = (string) $value;
            return function_exists('esc_attr')
                ? esc_attr($value)
                : htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        };
        $url = static function ($value) use ($attr) {
            $value = (string) $value;
            return function_exists('esc_url') ? esc_url($value) : $attr($value);
        };
        $asset = static function ($path) {
            $path = (string) $path;
            if (preg_match('#^(?:https?:)?//#i', $path) || strpos($path, '/') === 0) {
                return $path;
            }
            if (function_exists('get_theme_file_uri')) {
                return get_theme_file_uri(ltrim($path, '/'));
            }
            if (function_exists('get_template_directory_uri')) {
                return rtrim(get_template_directory_uri(), '/') . '/' . ltrim($path, '/');
            }
            return $path;
        };
        $link = static function ($path) {
            return function_exists('home_url') ? home_url($path) : $path;
        };

        $items = array();
        foreach ($releases as $release) {
            $title = (string) $read($release, 'title');
            if ($title === '' && is_object($release) && function_exists('get_the_title')) {
                $title = (string) get_the_title($release);
            }
            if ($title === '') {
                continue;
            }

            $image = $read($release, 'image', $read($release, 'img', $read($release, 'cover')));
            if (is_array($image)) {
                $image = $image['url'] ?? ($image['src'] ?? '');
            }
            if ($image === '' && is_object($release) && isset($release->ID)
                && function_exists('get_post_thumbnail_id')
                && function_exists('wp_get_attachment_image_url')) {
                $image_id = get_post_thumbnail_id($release->ID);
                $image = $image_id ? wp_get_attachment_image_url($image_id, 'large') : '';
            }

            $topic = trim((string) $read($release, 'topic'));
            if ($topic === '') {
                $topic = trim($title);
            }
            $tag = (string) $read($release, 'tag', $read($release, 'media'));
            $media = preg_split('/\s*[&,\/]\s*/', $tag);
            $media = trim((string) ($media[0] ?? ''));

            $lessons = $read($release, 'lessons', $read($release, 'lesson_count'));
            if (is_array($lessons)) {
                $lessons = count($lessons);
            }
            $level = (string) $read($release, 'level', $read($release, 'difficulty'));
            $level = str_replace('-', ' ', $level);
            $level = $level !== '' ? strtoupper(substr($level, 0, 1)) . substr($level, 1) : '';
            $href = (string) $read($release, 'url', $read($release, 'href', $read($release, 'permalink')));
            if ($href === '' && is_object($release) && function_exists('get_permalink')) {
                $href = (string) get_permalink($release);
            }
            if ($href === '') {
                $href = $link('/');
            }

            $date = (string) $read($release, 'date', $read($release, 'post_date'));
            $items[] = array(
                'title'    => $title,
                'topic'    => $topic,
                'tag'      => $media,
                'image'    => $image,
                'srcset'   => (string) $read($release, 'srcset'),
                'sizes'    => (string) $read($release, 'sizes'),
                'width'    => $read($release, 'width'),
                'height'   => $read($release, 'height'),
                'lessons'  => $lessons === '' ? 0 : $lessons,
                'level'    => $level,
                'href'     => $href,
                'price'    => $read($release, 'price', 0),
                'was'      => $read($release, 'was'),
                'date'     => $date,
            );
        }
        usort($items, static function ($a, $b) {
            return strcmp($b['date'], $a['date']);
        });
        $items = array_slice($items, 0, 3);

        $free_href = $link('/free-videos/');
        $about_href = $link('/about/');
        ?>
        <section class="pg-section">
            <div class="pg-wrap">
                <div class="release-head">
                    <h2 class="section-title is-left">New release</h2>
                </div>
                <div class="grid-12 release-trio">
                    <div class="span-4 reveal-up" style="--i:0">
                        <a class="free-video" href="<?php echo $url($free_href); ?>" aria-label="Free videos">
                            <img src="<?php echo $url($asset('img/A2.jpeg')); ?>" alt="Watercolour sketchbook — free videos preview" decoding="async">
                            <div class="fv-veil">
                                <div class="fv-play" aria-hidden="true">▶</div>
                                <strong class="font-a">Free videos</strong>
                                <span>Watch, Learn &amp; Create - Explore quick painting tutorials designed to inspire your artistic journey today.</span>
                            </div>
                        </a>
                    </div>

                    <div class="span-4 reveal-up" style="--i:1">
                        <div class="rel-carousel" id="rel-carousel" aria-roledescription="carousel" aria-label="New releases">
                            <span class="rel-chip">New release</span>
                            <div class="rel-dots" id="rel-dots"></div>
                            <button class="cas-pause" type="button" data-rel-pause aria-pressed="false" aria-label="Pause the new releases carousel">
                                <svg class="ico-pause" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><rect x="6" y="5" width="4" height="14" rx="1"></rect><rect x="14" y="5" width="4" height="14" rx="1"></rect></svg>
                                <svg class="ico-play" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5.2v13.6L19 12z"></path></svg>
                            </button>
                            <?php foreach ($items as $index => $item) :
                                $fit = strlen($item['topic']) <= 12 ? '' : (strlen($item['topic']) <= 22 ? ' is-mid' : ' is-long');
                                $image = $item['image'] !== '' ? $asset($item['image']) : '';
                                ?>
                                <div class="rel-slide<?php echo $index === 0 ? ' is-active' : ''; ?>" role="group" aria-roledescription="slide" aria-label="<?php echo $attr(($index + 1) . ' of ' . count($items)); ?>"<?php echo $index === 0 ? '' : ' aria-hidden="true" inert'; ?>>
                                    <?php if ($image !== '') : ?>
                                        <img src="<?php echo $url($image); ?>"<?php echo $item['srcset'] !== '' ? ' srcset="' . $attr($item['srcset']) . '"' : ''; ?> sizes="<?php echo $attr($item['sizes'] !== '' ? $item['sizes'] : '(max-width: 600px) 358px, (max-width: 1000px) 420px, 373px'); ?>"<?php echo is_numeric($item['width']) && is_numeric($item['height']) ? ' width="' . $attr($item['width']) . '" height="' . $attr($item['height']) . '"' : ''; ?> alt="<?php echo $attr($item['title']); ?>"<?php echo $index === 0 ? ' fetchpriority="high"' : ' fetchpriority="low"'; ?> decoding="async">
                                    <?php endif; ?>
                                    <div class="rel-title<?php echo $fit; ?>" title="<?php echo $attr($item['title']); ?>"><?php echo $html($item['topic']); ?></div>
                                    <div class="rel-facts">
                                        <span><i>Media</i><?php echo $html($item['tag']); ?></span>
                                        <span><i>Lessons</i><?php echo $html($item['lessons']); ?></span>
                                        <span><i>Level</i><?php echo $html($item['level']); ?></span>
                                    </div>
                                    <div class="rel-actions">
                                        <button class="cc-btn primary" type="button" data-add-to-cart data-tiered data-title="<?php echo $attr($item['title']); ?>" data-price="<?php echo $attr($item['price']); ?>"<?php echo $item['was'] !== '' ? ' data-was="' . $attr($item['was']) . '"' : ''; ?> data-img="<?php echo $attr($item['image']); ?>">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="21" r="1.6"></circle><circle cx="19" cy="21" r="1.6"></circle><path d="M2 3h3l2.6 12.6a1.8 1.8 0 0 0 1.8 1.4h8.9a1.8 1.8 0 0 0 1.8-1.4L22 7H6"></path></svg>
                                            Add to cart
                                        </button>
                                        <a class="cc-btn secondary" href="<?php echo $url($item['href']); ?>">View <span class="arr" aria-hidden="true">→</span></a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="span-4 about-col reveal-up" style="--i:2">
                        <a class="arch-link" href="<?php echo $url($about_href); ?>" aria-label="Meet Crystal — about me">
                            <div class="arch-duo">
                                <img src="<?php echo $url($asset('img/A3.jpeg')); ?>" alt="Crystal — founder portrait in the twin-arch mask" decoding="async">
                            </div>
                        </a>
                        <div class="arch-cap">
                            <div class="eyebrow-lbl">About Crystal</div>
                            <div class="social-row" data-cas-social="social-btn"></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <?php
    }
}
