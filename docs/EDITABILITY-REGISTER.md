# Editability register — Crystal Art School

Written 2026-08-23. Revised the same day after the Showcase / Blog / Free Videos build,
and again after the homepage, account and archive build.
One row per field: what the direction document decided, what the shipped theme actually
does, and where the two disagree.

`docs/20260804_Design_Direction.md` is cited as `DD:<line>`. Code paths are relative to
`wp-content/themes/hello-elementor-child/`. The DD copy behind the historical line citations is not in the repository.

The candidate Gallery rows below describe the pending source; native Review, the guarded size migration and deployment remain open.

**284 rows: 196 OK, 41 DEFECT, 47 UNDECIDED.**

Counted from the TSV, not estimated. Previously 221 rows: 138 OK, 37 DEFECT, 46 UNDECIDED.
The homepage build added 54 rows because five sections that had one row each — "whole
section, not implemented" — now have a row per field.

Rows carrying `decided_by = Herman's Rule 2026-08-23` are the ones the rule at the bottom
of this file settled. The direction document is silent on them; they are no longer
`UNDECIDED` because a decision now exists, not because a chapter was found.

Three statuses, and the third is the point of the exercise:

| Status | Meaning |
|---|---|
| `OK` | The deciding authority — a chapter, or Herman's Rule — and the code agree. |
| `DEFECT` | They disagree — in **either** direction. See the three classes below. |
| `UNDECIDED` | Nothing settles the field: no chapter names it, and Herman's Rule does not reach it either. |

`DEFECT` covers every direction deliberately. **Class A** is the document opening a field
the code hardcodes — the failure everyone expects. **Class B** is the code opening a field
no chapter names, which §1.4.14 closes by default. Class B is mostly benign and mostly
intended (the whole Tutor course surface sits there), but it is unwritten, so there is no
baseline for "she changed it and the page broke". **Class C** is new: Herman's Rule opens
a field and the 2026-08-23 build hardcoded it anyway, or opened it in a way that does not
hold.

A row can read `editable = yes` and still be `UNDECIDED`: the code opened it, and nothing
settles whether it should be open.

The machine-readable copy is `docs/editability-register.tsv`.

---

`DD:` = `docs/20260804_Design_Direction.md`. Code paths are relative to the theme snapshot root. `editable` = what the shipped theme actually permits; a row can read `yes` and still be `UNDECIDED` (code opens it, no chapter settles it). `1.4.14 default-closed` = DD:309, closed by the global rule because no chapter names the field.

## 1. THE REGISTER

```
page	field	editable	edited_where	decided_by	status
Site-wide	Any field no chapter names (default posture)	no	-	DD:309	OK
Site-wide	Button text and destinations, section headings, icons, layout, colour, fonts (general lock)	no	-	DD:313	OK
Site-wide	Drag-and-drop page layout, all pages except Blog	no	-	DD:40	OK
Site-wide	Light/dark theme mode (functions.php:66-68)	no	-	DD:313	OK
Site-wide	Header logo image, cas_logo (cas-brand.php:22,74)	yes	Customize > Site-wide > Logo and signature	1.4.14 default-closed	DEFECT
Site-wide	Hero signature mark / Logo2, cas_logo2 (cas-brand.php:27,74)	yes	Customize > Site-wide > Logo and signature	1.4.14 default-closed	DEFECT
Site-wide	Footer signature image, cas_signature (cas-brand.php:32,74)	yes	Customize > Site-wide > Logo and signature	1.4.14 default-closed	DEFECT
Site-wide	Instagram URL, cas_social_instagram (cas-editable.php:23,69)	yes	Customize > Site-wide > Contact and social links	DD:506-508	OK
Site-wide	Facebook URL, cas_social_facebook (cas-editable.php:24,69)	yes	Customize > Site-wide > Contact and social links	DD:506,509	OK
Site-wide	YouTube URL, cas_social_youtube (cas-editable.php:25,69)	yes	Customize > Site-wide > Contact and social links	DD:506,510	OK
Site-wide	Contact email, cas_social_email (cas-editable.php:26,69)	yes	Customize > Site-wide > Contact and social links	DD:506,511	OK
Site-wide	Primary nav labels and order (functions.php:95-105; cas-header.php:51)	yes	Appearance > Menus	1.4.14 default-closed	DEFECT
Site-wide	Scroll-reveal animation and the screen-reader announcement region (js/cas-site-extras.js)	no	-	Herman's Rule 2026-08-23	OK
Header	Logo alt text "Lau. - Crystal's Art School" (cas-header.php:79)	no	-	1.4.14 default-closed	UNDECIDED
Header	Cart icon aria-label and destination, slug checkout (cas-header.php:83)	no	-	DD:313	OK
Header	Cart badge count, literal 0 (cas-header.php:85)	no	-	1.4.14 default-closed	UNDECIDED
Header	"Log in" / "Sign up" labels and destinations (cas-header.php:87-88)	no	-	DD:313	OK
Header	Dark-mode toggle label (cas-header.php:89)	no	-	DD:313	OK
Header	"Menu" toggle label (cas-header.php:94)	no	-	DD:313	OK
Header	Courses mega-drawer, not implemented (cas-header.php:11-13)	no	-	1.4.14 default-closed	UNDECIDED
Home - Hero carousel	Slide 1/2/3 Main Title, cas_hero_N_title (cas-hero.php:114)	yes	Customize > Pages > Home	DD:468-469	OK
Home - Hero carousel	Slide 1/2/3 Description, cas_hero_N_subtitle (cas-hero.php:114)	yes	Customize > Pages > Home	DD:468,470	OK
Home - Hero carousel	Slide 1/2/3 large image, cas_hero_N_large (cas-hero.php:131)	yes	Customize > Pages > Home	DD:468,471	OK
Home - Hero carousel	Slide 1/2/3 small image, cas_hero_N_small (cas-hero.php:131)	yes	Customize > Pages > Home	DD:468,472	OK
Home - Hero carousel	Backend recommended size/ratio hint on both image fields (cas-hero.php:32,42,47)	no	-	DD:473	OK
Home - Hero carousel	Image crop and display ratio (cas-hero.php:131)	no	-	DD:474	OK
Home - Hero carousel	VIEW COURSES button label (cas-hero.php:174)	no	-	DD:467	OK
Home - Hero carousel	VIEW COURSES destination, slug courses (cas-hero.php:142-143)	no	-	DD:467	OK
Home - Hero carousel	Number of slides, const CAS_HERO_SLIDES = 3 (cas-hero.php:19)	no	-	1.4.14 default-closed	UNDECIDED
Home - Hero carousel	Carousel chrome: aria labels, pause, prev/next, counter (cas-hero.php:149,189-196)	no	-	DD:313	OK
Home - Free Videos promo card	Card photograph, cas_home_free_videos_photo, crops square (cas-home-free-videos.php)	yes	Customize > Pages > Home - Free videos card	Herman's Rule 2026-08-23	OK
Home - Free Videos promo card	Hover description, cas_home_free_videos_text (cas-home-free-videos.php)	yes	Customize > Pages > Home - Free videos card	Herman's Rule 2026-08-23	OK
Home - Free Videos promo card	Card image alt text, taken from the media library entry; no alt is forced in code	yes	Media library	Herman's Rule 2026-08-23	OK
Home - Free Videos promo card	Backend recommended size hint, 750x750 square, printed on the photo field	no	-	DD:476	OK
Home - Free Videos promo card	Title "Free videos", play mark, whole-card link to the Free videos page (cas-home-free-videos.php)	no	-	DD:313	OK
Home - Free Videos promo card	Description always visible on touch devices, @media (hover:none) in the shipped CSS	no	-	not decided (DD 3.3 open item)	UNDECIDED
Home - About Crystal	The section itself, built as the third column of the New release row (cas-home-about.php)	no	-	DD:504-512	OK
Home - About Crystal	About photograph, cas_home_about_image, crops 1/1 to its slot (cas-home-about.php)	yes	Customize > Pages > Home - About Crystal	DD:506-507	OK
Home - About Crystal	Backend recommended size hint, 840x840 square, printed on the photo field	no	-	DD:476	OK
Home - About Crystal	Photograph alt text, hardcoded "Crystal - founder portrait in the twin-arch mask" while the picture is swappable	no	-	Herman's Rule 2026-08-23	DEFECT
Home - About Crystal	Bottom-weighted crop applied only to the shipped photograph; an upload centres (cas-home-about.php)	no	-	Herman's Rule 2026-08-23	OK
Home - About Crystal	The four social buttons, one shared set with Follow along and the footer	yes	Customize > Site-wide > Contact and social links	DD:506,508-512	OK
Home - About Crystal	Body copy under the label: none specified by the chapter, none written	no	-	DD:506-508	OK
Home - About Crystal	ABOUT CRYSTAL heading wording	no	-	DD:504,512	OK
Home - Shop All Courses	Introduction paragraph, cas_home_shop_lede (cas-home-courses.php)	yes	Customize > Pages > Home - Shop all courses	Herman's Rule 2026-08-23	OK
Home - Shop All Courses	Emptying the introduction renders no introduction; the shipped copy is not restored	no	-	Herman's Rule 2026-08-23	OK
Home - Shop All Courses	Heading "Shop all courses" (cas-home-courses.php)	no	-	DD:313	OK
Home - Shop All Courses	Course Overview lede 1, cas_home_overview_lede_1	yes	Customize > Pages > Home - Course overview	decision 2026-08-24 (register brief 3, issue #26)	OK
Home - Shop All Courses	Course Overview lede 2, cas_home_overview_lede_2	yes	Customize > Pages > Home - Course overview	decision 2026-08-24 (register brief 3, issue #26)	OK
Home - Shop All Courses	Course Overview principle 01 title and text	yes	Customize > Pages > Home - Course overview	decision 2026-08-24 (register brief 3, issue #26)	OK
Home - Shop All Courses	Course Overview principle 02 title and text	yes	Customize > Pages > Home - Course overview	decision 2026-08-24 (register brief 3, issue #26)	OK
Home - Shop All Courses	Course Overview questions, post title (cas_overview_qa post type)	yes	Dashboard > Course overview questions	decision 2026-08-24 (register brief 3, issue #26)	OK
Home - Shop All Courses	Course Overview questions, post content (cas_overview_qa post type)	yes	Dashboard > Course overview questions	decision 2026-08-24 (register brief 3, issue #26)	OK
Home - Shop All Courses	Course Overview heading, numerals and section structure	no	-	Herman's Rule 2026-08-24	OK
Home - Shop All Courses	Rail cap of ten cards, the two-arrow pager and its step (cas-home-courses.php)	no	-	Herman's Rule 2026-08-23	OK
Home - Shop All Courses	Rail order, menu_order ASC then date DESC	yes	Tutor LMS > Courses > Page attributes	1.4.14 default-closed	DEFECT
Home - Course card	Random lower photograph, drawn per request from the four small course photographs (cas-home-courses.php)	no	-	DD:557	OK
Home - Course card	The four small course photographs themselves, _cas_course_photo_1..4	yes	Tutor LMS > Courses	not decided (DD:750)	UNDECIDED
Home - Course card	Fallback "Course photographs to come" where a course has no small photographs	no	-	Herman's Rule 2026-08-23	OK
Home - Course card	Cover crop 4/3 and lower photograph crop 16/9, both crop to slot	no	-	Herman's Rule 2026-08-23	OK
Home - Course card	Card facts, Lessons count and Level; no total runtime on the homepage card	no	-	DD 1.4.7	OK
Home - Course card	No Sale ribbon on the homepage card; the struck former price stays	no	-	DD 3.5.2	OK
Home - Course card	Media tag, title, regular and sale price, ADD button, all read from Tutor (cas-home-courses.php)	yes	Tutor LMS > Courses	1.4.14 default-closed	DEFECT
Home - Art Gallery promo card	Hover line, cas_home_gallery_sub (cas-home-gallery.php)	yes	Customize > Pages > Home - art gallery card	DD 3.6	OK
Home - Art Gallery promo card	Card picture, the featured image of the first published artwork; no second store (cas-home-gallery.php)	yes	Dashboard > Artwork	Herman's Rule 2026-08-23	OK
Home - Art Gallery promo card	Which artwork leads, menu_order ASC then date DESC; Gallery independently starts with newest publication date	yes	Dashboard > Artwork > Page attributes	Herman's Rule 2026-08-23	OK
Home - Art Gallery promo card	Heading "Art Gallery", the mark and the link to the Gallery page	no	-	DD:313	OK
Home - Art Gallery promo card	The card renders nothing at all until an artwork is published (cas-home-gallery.php)	no	-	Herman's Rule 2026-08-23	OK
Home - Art Gallery promo card	Emptying the hover line drops the line rather than restoring the shipped default	no	-	Herman's Rule 2026-08-23	OK
Home - Blog and Tools	Which articles show, the six newest published posts carrying a featured image (cas-home-blog.php)	yes	Dashboard > Posts	Herman's Rule 2026-08-23	OK
Home - Blog and Tools	Card title, picture and topic, core post fields	yes	Dashboard > Posts	Herman's Rule 2026-08-23	OK
Home - Blog and Tools	A post with no featured image is skipped from the rail	no	-	Herman's Rule 2026-08-23	OK
Home - Blog and Tools	Rail cap of six articles, two cards at a time, one at a time below 768px	no	-	Herman's Rule 2026-08-23	OK
Home - Blog and Tools	TOOLS card background photograph, cas_home_tools_photo, optional, crops to its square	yes	Customize > Pages > Home - blog and tools	Herman's Rule 2026-08-23	OK
Home - Blog and Tools	BLOG heading, TOOLS wording and the wrench mark	no	-	DD:313	OK
Home - Blog and Tools	TOOLS card destination, the Blog page filtered to the Tools topic	no	-	DD 3.7	OK
Home - Blog and Tools	Card excerpt, date and reading time, removed by the chapter	no	-	DD 3.7	OK
Home - Community band	Eyebrow "Fellow tier", heading, button "See the tiers" (cas-home.php:28-32)	no	-	DD:313	OK
Home - Community band	Body paragraph about the Fellow tier (cas-home.php:30)	no	-	1.4.14 default-closed	UNDECIDED
Home - Community band	Image img/Rubber.jpg and its alt text (cas-home.php:17,25)	no	-	1.4.14 default-closed	UNDECIDED
Home - Real Hands	Which three testimonials show, menu_order (cas-editable.php:223)	yes	Dashboard > Testimonials	DD:617	OK
Home - Real Hands	Which member the card is about, _cas_user_id (cas-editable.php:168,213)	yes	Dashboard > Testimonials > Who said it	DD:618-619	OK
Home - Real Hands	Displayed username, from display_name (cas-editable.php:235)	no	-	DD:619-621	OK
Home - Real Hands	Displayed profile picture, from get_avatar_url (cas-editable.php:236)	no	-	DD:619-621	OK
Home - Real Hands	Comment text, post content (cas-editable.php:234)	yes	Dashboard > Testimonials	DD:620-621	OK
Home - Real Hands	Heading "Real hands, real progress" (cas-home.php:63)	no	-	DD:313	OK
Home - Real Hands	Card caption "Profile picture and username - from the member record" (cas-home.php:71)	no	-	1.4.14 default-closed	UNDECIDED
Home - Social strip	Heading "Follow along" (cas-home.php:97)	no	-	DD:313	OK
Home	Course rail and blog rail, both built and both fed by published content	yes	Tutor LMS > Courses and Dashboard > Posts	Herman's Rule 2026-08-23	OK
Home - New release carousel	The whole block, still not implemented; the New release row renders two of its three columns	no	-	DD:686,694	DEFECT
Home - New release carousel	"New release" heading, deliberately not printed while the carousel is missing (cas-home-release.php)	no	-	DD:686	OK
Courses overview	Course title (cas-courses.php:101,109)	yes	Tutor LMS > Courses	1.4.14 default-closed	DEFECT
Courses overview	Course cover, featured image (cas-courses.php:83)	yes	Tutor LMS > Courses	1.4.14 default-closed	DEFECT
Courses overview	Regular price and sale price (cas-courses.php:59-63)	yes	Tutor LMS > Courses	1.4.14 default-closed	DEFECT
Courses overview	Currency symbol (cas-courses.php:46-48)	yes	Tutor LMS > Settings	1.4.14 default-closed	DEFECT
Courses overview	Level, _tutor_course_level (cas-courses.php:66)	yes	Tutor LMS > Courses	1.4.14 default-closed	DEFECT
Courses overview	Category tag, course-category first term (cas-courses.php:67)	yes	Tutor LMS > Courses	1.4.14 default-closed	DEFECT
Courses overview	Catalogue order, menu_order then date DESC (cas-courses.php:139)	yes	Tutor LMS > Courses	1.4.14 default-closed	DEFECT
Courses overview	Cart page URL, tutor_cart_page_id (cas-courses.php:200)	yes	Tutor LMS > Settings	1.4.14 default-closed	DEFECT
Courses overview	Lesson count and runtime, derived (cas-courses.php:25,69)	no	-	DD:793	OK
Courses overview	Best-selling five, not implemented	no	-	DD:646-648	OK
Courses overview	Page intro and its Read More cut point, not implemented	no	-	not decided (DD:656)	UNDECIDED
Courses overview	THREE WAYS TO STUDY tier description text, cas_tier_*_text (cas-courses.php:459,500-505)	yes	Customize > Pages > Courses - Three ways to study	DD:187	OK
Courses overview	THREE WAYS TO STUDY card background photo, cas_tier_*_photo, crops to the card (cas-courses.php:487,556)	yes	Customize > Pages > Courses - Three ways to study	DD:187	OK
Courses overview	Backend hint "pick a darker or low-contrast photo", on both photo fields (cas-courses.php)	no	-	DD:192	OK
Courses overview	Tier names Wanderer / Apprentice / Fellow (cas-courses.php)	no	-	DD:188-190	OK
Courses overview	Tier price lines Free / Per-course purchase / Per-course + Discord (cas-courses.php)	no	-	DD:188	OK
Courses overview	most picked marker on Apprentice (cas-courses.php)	no	-	DD:189	OK
Courses overview	Tier button text and link; Wanderer's renders nothing while every course is a draft (cas-courses.php:514,542,563-566)	no	-	DD:189	OK
Courses overview	FREE TRIAL band, still not implemented	no	-	DD:193-195	DEFECT
Courses overview	Card labels "Add to cart" / "View course" / "Sale" (cas-courses.php:107,113,115)	no	-	DD:313	OK
Courses overview	Card image alt template "{title} - {tag} course" (cas-courses.php:101)	no	-	1.4.14 default-closed	UNDECIDED
Courses overview	"N course(s)" count wording (cas-courses.php:153)	no	-	1.4.14 default-closed	UNDECIDED
Courses overview	Lesson/lessons wording, tilde runtime prefix, chip separator (cas-courses.php:74-75,119)	no	-	1.4.14 default-closed	UNDECIDED
Courses overview	Empty state "No courses are published yet." (cas-courses.php:151)	no	-	1.4.14 default-closed	UNDECIDED
Courses overview	Filter and sort rail, derived from the published courses (cas-courses.php:338,344)	no	-	Herman's Rule 2026-08-23	OK
Courses overview	Which filter groups appear; a group renders only where it can change the result (cas-courses.php:308-311)	no	-	Herman's Rule 2026-08-23	OK
Courses overview	Length band labels, price slider steps and sort option labels (cas-courses.php)	no	-	Herman's Rule 2026-08-23	OK
Courses overview	Page hero, eyebrow "Course catalog" and title "Pick up a brush.", not implemented	no	-	DD:670	DEFECT
Course detail	Description / blurb, course post_content (cas-course-single.php:103)	yes	Tutor LMS > Courses	1.4.14 default-closed	DEFECT
Course detail	"What you will learn" items, _tutor_course_benefits (cas-course-single.php:58)	yes	Tutor LMS > Courses	1.4.14 default-closed	DEFECT
Course detail	"Who it is for" cards, _tutor_course_target_audience (cas-course-single.php:59,190)	yes	Tutor LMS > Courses	not decided (DD:855-861)	UNDECIDED
Course detail	"What comes with it" list, _tutor_course_material_includes (cas-course-single.php:60)	yes	Tutor LMS > Courses	1.4.14 default-closed	DEFECT
Course detail	Course cover, the one large image (cas-course-single.php:84)	yes	Tutor LMS > Courses	not decided (DD:750)	UNDECIDED
Course detail	The four secondary images, thumbnail strip not implemented (cas-course-single.php:10-12)	no	-	not decided (DD:750)	UNDECIDED
Course detail	CTA state Add to Cart / View Course, hardcoded "Add to cart" (cas-course-single.php:94)	no	-	DD:775-777	OK
Course detail	Total video runtime, computed (cas-courses.php:25)	no	-	DD:793	OK
Course detail	Media / Lessons count / difficulty display (cas-course-single.php:27-35,55)	no	-	1.4.14 default-closed	UNDECIDED
Course detail	Refund clause text, not rendered on this page	no	-	not decided (DD:817,828-830)	UNDECIDED
Course detail	Syllabus topics, topics post type (cas-course-single.php:62-70)	yes	Tutor LMS > Courses > Topics	1.4.14 default-closed	DEFECT
Course detail	Backend field label Module renamed to Phase	no	-	not decided (DD:852,1541)	UNDECIDED
Course detail	Section eyebrows and headings, all six (cas-course-single.php:115-116,129-130,181-183,205-206)	no	-	DD:313	OK
Course detail	Breadcrumb "Courses", "Add to cart", "Free preview" chip (cas-course-single.php:76,94,161)	no	-	DD:313	OK
Course detail	"test your own photo" block, not implemented (cas-course-single.php:10-12)	no	-	1.4.14 default-closed	UNDECIDED
Lesson page	lesson_title, lesson post title (cas-course-single.php:159)	yes	Tutor LMS > Lessons	DD:241	OK
Lesson page	lesson_video[], _video meta (cas-course-single.php:149-151)	yes	Tutor LMS > Lessons	DD:241	OK
Lesson page	lesson_cover_image, no lesson-page template in the theme	no	-	DD:241	DEFECT
Lesson page	materials[], no lesson-page template in the theme	no	-	DD:241	DEFECT
Lesson page	One-line subtitle, lesson post_content (cas-course-single.php:165)	yes	Tutor LMS > Lessons	DD:242	OK
Lesson page	lesson_description, field cancelled	no	-	DD:239	OK
Lesson page	Image-and-text block reads the course-level blurb (cas-course-single.php:103)	yes	Tutor LMS > Courses	DD:240,884-885	OK
Lesson page	Manual timecode and chapter-title fields, not created	no	-	DD:936	OK
Lesson page	Your Progress, lesson list, page footer	no	-	1.4.14 default-closed	UNDECIDED
Gallery	Artwork image, featured image (cas-gallery.php:603)	yes	Dashboard > Artwork	DD:1004	OK
Gallery	Artwork name, post title (cas-gallery.php:608)	yes	Dashboard > Artwork	DD:1004	OK
Gallery	Description, post content (cas-gallery.php:652)	yes	Dashboard > Artwork	DD:1004	OK
Gallery	Price, _cas_artwork_price (cas-gallery.php:11,78,207)	yes	Dashboard > Artwork > Sale details	DD:1004	OK
Gallery	Etsy URL, _cas_artwork_etsy_url (cas-gallery.php:12,90,208)	yes	Dashboard > Artwork > Sale details	DD:1004	OK
Gallery	Stock status, _cas_artwork_stock (cas-gallery.php:13,102,209)	yes	Dashboard > Artwork > Sale details	DD:1004	OK
Gallery	Curation / date-tiebreak, menu_order (cas-gallery.php:377-380)	yes	Dashboard > Artwork > Page Attributes > Order	DD:1004	OK
Gallery	Medium, _cas_artwork_medium (cas-gallery.php:14,114,210)	yes	Dashboard > Artwork > Sale details	Gallery medium-filter decision 2026-08-28	OK
Gallery	Size, _cas_artwork_size (cas-gallery.php:16,125,212)	yes	Dashboard > Artwork > Sale details	User grant 2026-10-28	OK
Gallery	Optional type, _cas_artwork_type (cas-gallery.php:15,125,211)	yes	Dashboard > Artwork > Sale details	User grant 2026-10-28	OK
Gallery	On-site Add to Cart, absent from card and modal (cas-gallery.php:692-696,770-771)	no	-	DD:999-1000	OK
Gallery	Introduction paragraph, Crystal's own text unrewritten (cas-gallery.php:426)	no	-	DD:962	OK
Gallery	Hero eyebrow and title (cas-gallery.php:415)	no	-	DD:313	OK
Gallery	"Buy on Etsy" / "View details" / "Sold" labels (cas-gallery.php:680,693,695)	no	-	DD:313	OK
Gallery	Modal chrome, Close / close glyph / title placeholder (cas-gallery.php:750-762)	no	-	DD:313	OK
Gallery	Ornate frame graphic deer-black.svg (cas-gallery.php:730-731)	no	-	DD:313	OK
Gallery	Empty state "No artwork is published yet." (cas-gallery.php:446)	no	-	1.4.14 default-closed	UNDECIDED
Gallery	Was-price and Sale ribbon remain absent; no client source (cas-gallery.php:665-699)	no	-	1.4.14 default-closed	UNDECIDED
Gallery	Medium and price filters, date/price sorting, optional type filter (cas-gallery.php:469-558)	no	-	User instructions 2026-10-27 and 2026-10-28	OK
Showcase	Piece title, post title (cas-showcase.php)	yes	Dashboard > Student work	Herman's Rule 2026-08-23	OK
Showcase	Photograph, featured image; a piece without one is skipped (cas-showcase.php)	yes	Dashboard > Student work	Herman's Rule 2026-08-23	OK
Showcase	Student credit, _cas_showcase_student (cas-showcase.php:26)	yes	Dashboard > Student work > Credit	Herman's Rule 2026-08-23	OK
Showcase	Medium, _cas_showcase_medium, builds the filter pills (cas-showcase.php:27)	yes	Dashboard > Student work > Credit	Herman's Rule 2026-08-23	OK
Showcase	Wall order, menu_order ASC then date DESC (cas-showcase.php:273-276)	yes	Dashboard > Student work > Page attributes	Herman's Rule 2026-08-23	OK
Showcase	Whether the credit is a username or a real name, free text with no member link (cas-showcase.php:26,147-151)	yes	Dashboard > Student work > Credit	DD:622,634-635	DEFECT
Showcase	Page title "Look at their learning outcomes" and the "Student showcase" eyebrow (cas-showcase.php)	no	-	DD:1027	OK
Showcase	Cork wall layout, rows of five, pin colours and jitter (cas-showcase.php:453-471)	no	-	DD:1033	OK
Showcase	Filter pill set, derived from the media in use; hidden below two media (cas-showcase.php:420-422)	no	-	Herman's Rule 2026-08-23	OK
Showcase	CTA band eyebrow, heading and the "Join as a Fellow" button (cas-showcase.php:366)	no	-	DD:313	OK
Showcase	CTA band paragraph, states a monthly-critique process (cas-showcase.php:366)	no	-	Herman's Rule 2026-08-23	DEFECT
Showcase	Empty state and filter-empty wording (cas-showcase.php:355)	no	-	Herman's Rule 2026-08-23	OK
Blog - post body	Paragraph, Heading, Image, List, Quote, Separator, Gallery (cas-blog.php:48-66,93)	yes	Dashboard > Posts	DD:1078-1080	OK
Blog - post body	Columns, Group, Cover (cas-blog.php:48-66)	yes	Dashboard > Posts	DD:1081	OK
Blog - post body	Custom HTML, absent from the whitelist (cas-blog.php:48-66)	no	-	DD:1083-1084	OK
Blog - post body	Table, absent from the whitelist (cas-blog.php:48-66)	no	-	DD:1085-1087	OK
Blog - post body	Block Patterns, not registered (cas-blog.php:15-22)	no	-	DD:1089-1090	OK
Blog - post body	theme.json colour and typography controls, left open (cas-blog.php:15-22)	yes	Dashboard > Posts	not decided	UNDECIDED
Blog - list	Featured article selection, newest article, one card per topic (cas-blog-templates.php)	no	-	DD:1045	OK
Blog - list	Cover Quote field, not created; the feature card prints the article Title (cas-blog-templates.php)	no	-	DD:1047	OK
Blog - list	Load More batch size, fixed at 6 (cas-blog-templates.php)	no	-	Herman's Rule 2026-08-23	OK
Blog - list	Introduction paragraph, cas_blog_intro (cas-blog-templates.php)	yes	Customize > Pages > Blog	Herman's Rule 2026-08-23	OK
Blog - list	Topic chips, derived from the published posts' categories (cas-blog-templates.php)	yes	Dashboard > Posts > Categories	DD:1049	OK
Blog - list	Card images lazy-loaded; the hidden feature cards load eagerly (cas-blog-templates.php)	no	-	DD:1060	DEFECT
Blog - list	Hero eyebrow "From the studio", title "The journal", card labels, both empty states	no	-	DD:313	OK
Blog - single post page	Article title, body and featured image, core post (cas-blog-templates.php)	yes	Dashboard > Posts	DD:1078-1080	OK
Blog - single post page	Publish date and author byline, core post fields (cas-blog-templates.php)	yes	Dashboard > Posts	Herman's Rule 2026-08-23	OK
Blog - single post page	Topic label on the ribbon and in the byline, post category (cas-blog-templates.php)	yes	Dashboard > Posts > Categories	DD:1049	OK
Blog - single post page	Developer's note callout, _cas_blog_devnote (cas-blog-templates.php)	yes	Dashboard > Posts > Developer's note	Herman's Rule 2026-08-23	OK
Blog - single post page	Numbered list markers, from the List block's Start value (cas-blog-templates.php)	yes	Dashboard > Posts	Herman's Rule 2026-08-23	OK
Blog - single post page	Heading colour, alignment and HTML anchor, discarded by cas_blog_sectionise()	no	-	Herman's Rule 2026-08-23	DEFECT
Blog - single post page	Guide or essay layout, chosen by whether the body carries H2 headings	no	-	Herman's Rule 2026-08-23	OK
Blog - single post page	Reading time, computed from the word count	no	-	Herman's Rule 2026-08-23	OK
Blog - single post page	Banner, article, Keep reading and More from Crystal headings and chrome	no	-	DD:313	OK
Blog - single post page	Logo2 centred above the banner (cas-blog-templates.php)	no	-	DD:119-143	OK
Blog - single post page	Image crop slots: banner 48/9, card 3/2, rail 4/3, feature min-height	no	-	Herman's Rule 2026-08-23	OK
Blog - single post page	Recommended dimensions per image slot, no note anywhere in the admin	no	-	DD:476	DEFECT
Blog - single post page	Author bio card and comment form, not ported	no	-	not decided (DD:1548-1550)	UNDECIDED
About	All headings, eyebrows and card titles (cas-about.php:264,288,306,337-338,343,347,352,480-481,490,494,498,506-507,511,515,527-528,592-593)	no	-	DD:313	OK
About	Opening paragraph, cas_about_intro (cas-about.php:484-488)	yes	Customize > Pages > About	Herman's Rule 2026-08-23	OK
About	Quotation, cas_about_quote (cas-about.php:484-488)	yes	Customize > Pages > About	Herman's Rule 2026-08-23	OK
About	Artistic journey essay body, cas_about_journey_body (cas-about.php:484-488)	yes	Customize > Pages > About	Herman's Rule 2026-08-23	OK
About	Community essay body, cas_about_community_body (cas-about.php:484-488)	yes	Customize > Pages > About	Herman's Rule 2026-08-23	OK
About	Instructor essay body, cas_about_instructor_body (cas-about.php:484-488)	yes	Customize > Pages > About	Herman's Rule 2026-08-23	OK
About	Clearing any of the five text fields silently restores the shipped copy (cas-about.php:543-563)	yes	Customize > Pages > About	Herman's Rule 2026-08-23	DEFECT
About	Teaching philosophy prose and its three card bodies (cas-about.php:941-966)	no	-	Herman's Rule 2026-08-23	DEFECT
About	Welcome card bodies and the closing marker line (cas-about.php:967-985)	no	-	Herman's Rule 2026-08-23	DEFECT
About	Credentials section lead paragraph (cas-about.php:802)	no	-	Herman's Rule 2026-08-23	DEFECT
About	Portrait photograph, cas_about_portrait, crops 5/6 to its slot (cas-about.php:484-488,578-584)	yes	Customize > Pages > About	Herman's Rule 2026-08-23	OK
About	Portrait alt text, hardcoded "Crystal Lau in her studio" while the photograph is swappable	no	-	Herman's Rule 2026-08-23	DEFECT
About	Live-drawing carousel photographs, cas_about_photo set live-drawing (cas-about.php:279-308)	yes	Dashboard > About photos	Herman's Rule 2026-08-23	OK
About	Exhibition carousel photographs, cas_about_photo set exhibition (cas-about.php:279-308)	yes	Dashboard > About photos	Herman's Rule 2026-08-23	OK
About	Artful Living carousel photographs, cas_about_photo set artful-living (cas-about.php:279-308)	yes	Dashboard > About photos	Herman's Rule 2026-08-23	OK
About	Which set a photograph belongs to, _cas_about_photo_set, three fixed keys (cas-about.php:118)	yes	Dashboard > About photos > Which set	Herman's Rule 2026-08-23	OK
About	Carousel photo order and alt text, menu_order and post title (cas-about.php:293-308)	yes	Dashboard > About photos	Herman's Rule 2026-08-23	OK
About	The three carousel sets themselves, fixed in code (cas-about.php:118)	no	-	Herman's Rule 2026-08-23	OK
About	A set falls back to the shipped photographs until its first upload is published (cas-about.php:279-284)	no	-	Herman's Rule 2026-08-23	DEFECT
About	Honour rows: year, wording, certificate scan, caption (cas-about.php:402-424)	no	-	1.4.14 default-closed	UNDECIDED
About	Which certificate carries the highlight, data-highlight (cas-about.php:402)	no	-	1.4.14 default-closed	UNDECIDED
About	Demo scaffolding certificate rows and the visitor-facing note (cas-about.php:446-467)	no	-	1.4.14 default-closed	UNDECIDED
About	Credential claim sentences: SKY GALLERY, Repin, Sun Museum (cas-about.php:344,348)	no	-	1.4.14 default-closed	UNDECIDED
About	Exhibition poster row and caption (cas-about.php:440-444)	no	-	1.4.14 default-closed	UNDECIDED
About	Contact channel labels Email / Discord / Instagram / Facebook / Originals (cas-about.php:534-551)	no	-	DD:313	OK
About	Contact email address displayed (cas-about.php:534,677)	yes	Customize > Site-wide > Contact and social links	DD:506,511	OK
About	Instagram and Facebook handles displayed, derived from the URLs (cas-about.php:165-169)	yes	Customize > Site-wide > Contact and social links	DD:506,508-509	OK
About	Contact form labels, placeholders and button (cas-about.php:568-579)	no	-	DD:313	OK
About	Contact form validation and mail strings (cas-about.php:1098-1100,1120,1137-1138)	no	-	1.4.14 default-closed	UNDECIDED
About	Contact form server handler, mailto only (cas-about.php:1067-1074)	no	-	1.4.14 default-closed	UNDECIDED
About	Terms panel 1, purchases and refunds placeholder copy (cas-about.php:627-628)	no	-	DD:828-830	OK
About	Terms panels 2-4: licence, cookies, privacy rights, not implemented (cas-about.php:599-605)	no	-	not decided	UNDECIDED
About	Carousel and lightbox chrome, pause, dots, live-region strings (cas-about.php:644-647,733,742,794,802,813,875)	no	-	DD:313	OK
Certificates	Every field, rendered inside About, all hardcoded (cas-about.php:400-467)	no	-	1.4.14 default-closed	UNDECIDED
FAQ	Question, post title (cas-editable.php:146)	yes	Dashboard > FAQ	DD:1264	OK
FAQ	Answer, post content (cas-editable.php:147)	yes	Dashboard > FAQ	DD:1264	OK
FAQ	Order of questions, menu_order (cas-editable.php:138)	yes	Dashboard > FAQ > Page attributes	1.4.14 default-closed	DEFECT
FAQ	Page layout and structure (cas-pages.php:51-78)	no	-	DD:1264	OK
FAQ	Hero eyebrow and title, section headings, plus icon (cas-pages.php:51,56,63,73)	no	-	DD:313	OK
FAQ	"If your question isn't here, email ..." sentence (cas-pages.php:76-78)	no	-	1.4.14 default-closed	UNDECIDED
FAQ	Content of payment methods, refund window, access period, download limits, device requirements, failed payment	yes	Dashboard > FAQ	not decided (DD:1271-1278)	UNDECIDED
Free Videos	Film title, post title (cas-free-videos.php)	yes	Dashboard > Free videos	Herman's Rule 2026-08-23	OK
Free Videos	Cover photograph, featured image; a film without one is skipped (cas-free-videos.php:660-663)	yes	Dashboard > Free videos	Herman's Rule 2026-08-23	OK
Free Videos	Blurb, post body clamped to three lines (cas-free-videos.php)	yes	Dashboard > Free videos	Herman's Rule 2026-08-23	OK
Free Videos	Which section a film sits in, _cas_free_video_section (cas-free-videos.php:474-491)	yes	Dashboard > Free videos > Video details	DD:1223-1226	OK
Free Videos	Video link, YouTube or Bunny, _cas_free_video_url (cas-free-videos.php:231,247)	yes	Dashboard > Free videos > Video details	Herman's Rule 2026-08-23	OK
Free Videos	Order within a row, menu_order ASC then date DESC (cas-free-videos.php)	yes	Dashboard > Free videos > Page attributes	Herman's Rule 2026-08-23	OK
Free Videos	Opening line, cas_fv_lead (cas-free-videos.php:399; the setting id is cas_fv_lead, the register had cas_fv_intro_lede)	yes	Customize > Pages > Free videos	Herman's Rule 2026-08-23	OK
Free Videos	Three section introductions, cas_fv_intro_demos/quick/shorts (cas-free-videos.php:399)	yes	Customize > Pages > Free videos	DD:1228	OK
Free Videos	Courses link inside the demos introduction, frozen into the database once she edits it (cas-free-videos.php:399)	yes	Customize > Pages > Free videos	Herman's Rule 2026-08-23	DEFECT
Free Videos	Section set fixed at three, keys demos/quick/shorts (cas-free-videos.php:70-73)	no	-	DD:1223-1226	OK
Free Videos	Third section name "Shorts", the developer's proposal, not hers	no	-	not decided (DD:1233,1247)	UNDECIDED
Free Videos	Section headings and the featured heading (cas-free-videos.php)	no	-	DD:313	OK
Free Videos	Aspect ratio per section, 16:9 landscape and 9:16 Shorts (cas-free-videos.php)	no	-	DD:277	OK
Free Videos	Crystal's portrait beside the featured heading, hardcoded img/ProfilePic-96.jpg (cas-free-videos.php:626-645)	no	-	Herman's Rule 2026-08-23	DEFECT
Free Videos	Closing band paragraph (cas-free-videos.php:558-566)	no	-	Herman's Rule 2026-08-23	DEFECT
Free Videos	Closing band eyebrow, heading and button (cas-free-videos.php:558-566)	no	-	DD:313	OK
Free Videos	Duration badge and per-section empty-state sentence, both derived (cas-free-videos.php:539-543)	no	-	Herman's Rule 2026-08-23	OK
Archives (topic, tag, date, author, search)	Which posts appear, their titles, pictures, topics and dates (cas-archives.php)	yes	Dashboard > Posts	Herman's Rule 2026-08-23	OK
Archives (topic, tag, date, author, search)	Cards per page	yes	Settings > Reading	Herman's Rule 2026-08-23	OK
Archives (topic, tag, date, author, search)	Archive headings and eyebrows: Topic, Tag, Written by, Archive, Search results	no	-	Herman's Rule 2026-08-23	OK
Archives (topic, tag, date, author, search)	Lead line counts, measured from the query (cas-archives.php)	no	-	Herman's Rule 2026-08-23	OK
Archives (topic, tag, date, author, search)	Empty-state sentences on search, author, topic and tag archives	no	-	Herman's Rule 2026-08-23	OK
Archives (topic, tag, date, author, search)	Pager labels Newer and Older, and the "Page N of M" line	no	-	Herman's Rule 2026-08-23	OK
Archives (topic, tag, date, author, search)	"All posts" button and its destination	no	-	DD:313	OK
Archives (topic, tag, date, author, search)	Author archive heading prints the account login name, not a display name	no	-	Herman's Rule 2026-08-23	DEFECT
Archives (topic, tag, date, author, search)	No search field anywhere on the search results page	no	-	not decided	UNDECIDED
Archives (topic, tag, date, author, search)	Search results include WordPress Pages, chipped "Page"	no	-	not decided	UNDECIDED
Archives (topic, tag, date, author, search)	404 page, still the stock hello-elementor template	no	-	not decided	UNDECIDED
Login / Signup / Dashboard / Checkout	Page shells built; Tutor owns every form, the cart and the checkout (cas-account.php)	no	-	not decided (DD:1517-1524)	UNDECIDED
Login / Signup / Dashboard / Checkout	Eyebrows, headings and the three checkout step names (cas-account.php)	no	-	DD:313	OK
Login / Signup / Dashboard / Checkout	Lede sentences on Log in, Your studio and Your basket, hardcoded (cas-account.php)	no	-	Herman's Rule 2026-08-23	DEFECT
Login / Signup / Dashboard / Checkout	"New accounts are closed at the moment." shown while registration is off (cas-account.php)	no	-	Herman's Rule 2026-08-23	OK
Login / Signup / Dashboard / Checkout	Whether new accounts can be created, users_can_register	yes	Settings > General	not decided (DD:1517-1524)	UNDECIDED
Login / Signup / Dashboard / Checkout	Member name, avatar and join date in the dashboard band, from the WordPress user record	yes	Users > Profile	Herman's Rule 2026-08-23	OK
Login / Signup / Dashboard / Checkout	Enrolment count and completed-lesson count, computed from Tutor's own data	no	-	Herman's Rule 2026-08-23	OK
Login / Signup / Dashboard / Checkout	Tutor's own dashboard panels, its greeting and its login card heading, left as Tutor renders them	no	-	not decided (DD:1517-1524)	UNDECIDED
Login / Signup / Dashboard / Checkout	Membership tier chip on the dashboard, not built; nothing on this install records a tier	no	-	not decided (DD:1517-1524)	UNDECIDED
Footer	Tagline sentence about SKY GALLERY (cas-footer.php:79)	no	-	1.4.14 default-closed	UNDECIDED
Footer	Column headings Learn / Explore / Contact (cas-footer.php:83,89,96)	no	-	DD:313	OK
Footer	Link labels and destinations, six page slugs (cas-footer.php:84-101)	no	-	DD:313	OK
Footer	Social link labels and aria-labels (cas-footer.php:40,46,98-100)	no	-	DD:313	OK
Footer	Social link destinations (cas-footer.php:98-100)	yes	Customize > Site-wide > Contact and social links	DD:506,508-510	OK
Footer	Copyright line (cas-footer.php:104)	no	-	1.4.14 default-closed	UNDECIDED
```

## 2. THE DEFECT LIST

### Class A — the document opens it, the client cannot edit it (8 rows)

| # | Field | Document says | Code does | Size |
|---|---|---|---|---|
| A1 | ~~Home §3.4 About Crystal — **About 圖片**~~ | — | **Closed 2026-08-23.** The section is built (`inc/cas-home-about.php`) as the third column of the New release row, and the granted image is `cas_home_about_image` under Customize → Pages → Home — About Crystal, cropping 1/1 to its slot. The other four grants (IG/FB/YT/email) still resolve through the one shared Site-wide setting, per DD:512. | Resolved. Kept here so the closure is on the record. The card's alt text stayed hardcoded and is now Class C defect C9. |
| A2 | ~~Courses §1.4.8 THREE WAYS TO STUDY~~ | — | **Closed 2026-08-23.** Both granted fields now exist as `cas_tier_*_text` and `cas_tier_*_photo` under Customize → Pages → Courses — Three ways to study, and the DD:192 hint is printed on both photo fields. The locked half of the table (names, prices, `most picked`, button text) is built and locked. | Resolved. Kept here so the closure is on the record rather than the row simply disappearing. |
| A3 | Lesson page §8.1 — **`lesson_cover_image`** and **`materials[]`** | DD:241 names four per-lesson fields as the ones that remain open. | No lesson-page template exists in the theme. Only three lesson values reach the site, all inside the course syllabus: title (`cas-course-single.php:159`), sub-line (`:165`), runtime (`:149-151`). | Two of the four granted per-lesson fields, one of them the per-lesson materials list she is expected to maintain course by course. |
| A4 | Courses §1.4.8 — the **`FREE TRIAL` band** and the **page hero** | DD:193-195 names the band by its prototype class and its three parts. DD:670 sets `Pick up a brush.` in Font A; DD:126-128 places Logo2 right-aligned in its wood banner. | Neither is built. `/courses/` is the only content page on the site with no `<h1>` and no `page-hero`; `band-forest` returns no hits in the Courses module. | Two specified blocks missing from a commercial page. The hero one also costs the page its heading structure. |
| A5 | Showcase §3.9 — the credit shown under a print | DD:622 「顯示的是 username，不是真實姓名」, and DD:634-635 writes the member-terms clause around **username + avatar**. | `cas_showcase` stores a free-text field labelled "Student name" with no member link and no note about usernames (`cas-showcase.php:26,147-151`). | The consent basis on record covers a username. The admin invites a real name, and the terms panel that would authorise it is one of the three not implemented. |
| A6 | Blog §12.2 — **lazy loading** and **per-slot dimensions** | DD:1060 「文章圖片一律延遲載入」. DD:476 sets the precedent that recommended dimensions are published per image slot in the admin. | The hidden feature cards are emitted `loading="eager" fetchpriority="high"`, one per topic; the grid cards are correctly lazy. No dimension note appears anywhere on the Posts screen, while one featured image feeds four differently-shaped slots. | Latent today at one post and no real categories. It grows with the number of topics. |
| A7 | Home §5 — the **New release carousel** | DD:686 sets the short topic taken from the text before the hyphen; DD:694 mandates a backend input hint for it. Both describe a block the chapter assumes exists. | Not built. `inc/cas-home-release.php` renders the row wrapper and two of its three `.span-4` columns; grid columns 9-12 are empty. Measured at 1440px: the row ends 397px short of every other section on the page. | The most visible gap left on the homepage, and it is desktop-only — below 900px the two columns widen and the row is correct. The "New release" heading is deliberately not printed, because `industrial-1.css:766` pins it above the missing column, and a heading over an empty cell is worse than no heading. |

### Class B — the code opens what DD:309 closes by default (19 rows, 5 entries)

DD:309 「預設不開放；只有各章明文列出的欄位可以修改」. Every field below is editable in the shipped admin and is named in no chapter.

| # | Fields | Where she can change them | Nearest document statement | Size |
|---|---|---|---|---|
| B1 | The whole Tutor LMS course surface: title, cover, regular/sale price, currency, level, category, catalogue order, cart page URL (`cas-courses.php:46-48,59-67,83,101,109,139,200`); description/blurb, benefits, material includes, syllabus topics (`cas-course-single.php:58,60,62-70,103`) | Tutor LMS > Courses / Settings | §4.3 (DD:658-674) and §7.1-7.3 (DD:750,784-790) describe these cards in detail and never state editability. DD:750 says five images are uploaded per course, which A's inventory classes as "who supplies", not a CMS grant. | Largest open surface on the site and almost certainly intended — but with nothing in writing, there is no baseline for "she changed it and the page broke". Two related rules already assume auto-derivation she can defeat: DD:686 (short topic taken from text before the hyphen) and DD:694 (mandated backend input hint), neither of which exists because the New Release block does not exist. |
| B2 | `cas_logo`, `cas_logo2`, `cas_signature` (`cas-brand.php:22,27,32,74`) | Customize > Site-wide > Logo and signature | §2.3/§2.4 (DD:386-419) discuss the logos as an asset question only. DD:313 locks 圖示 generally; DD:133-134 fixes where Logo2 may appear. | Moderate. One `cas_logo2` swap changes every page hero at once (`cas-pages.php:30`), and the placement rule that governs it is a document decision she cannot see from the Customizer. |
| B3 | Primary nav labels and order (`functions.php:95-105`, rendered `cas-header.php:51,62`) | Appearance > Menus | §3.1 (DD:427-456) specifies 導覽次序 as a design decision. DD:313 locks 按鈕文字及目的地. The code comment states the opening is deliberate: "so their labels and order are manageable from Appearance > Menus without touching code". | Moderate. A menu edit silently overrides a specified nav order, and `fallback_cb=false` (`cas-header.php:51`) means an emptied menu renders no navigation at all. |
| B4 | FAQ question order, `menu_order` (`cas-editable.php:138`) | Dashboard > FAQ > Page attributes | DD:1264 opens 問答內容 and keeps the existing 版面. Order is named in neither half. | Small. Marginal — reordering answers is arguably "問答內容", but the sentence does not say so. |
| B5 | Homepage course rail: the ten cards' media tag, title, regular and sale price and ADD button, plus the rail order (`cas-home-courses.php`) | Tutor LMS > Courses (and Page attributes > Order) | §3.5.2 (DD:545-560) describes the three-part card in detail and never states editability. Same shape as B1, one level up. | Small in itself — it is B1's surface shown a second time. Recorded separately because the homepage is where a wrong price is seen first, and because the rail order is the Order box that already runs backwards relative to every other one on the site (trap T6). |

### Class C — Herman's Rule opens it, the 2026-08-23 build did not deliver it (14 rows)

The rule at the foot of this file puts body copy, photographs and item titles on Crystal's
side. These eleven rows are where the build either kept that content in PHP or opened it in
a way that does not hold up.

| # | Field | What the rule says | What the build does | Size |
|---|---|---|---|---|
| C1 | About — **teaching philosophy prose, welcome card bodies, credentials lead paragraph** (`cas-about.php:802,941-966,967-985`) | Body copy is hers. | Opened the opening paragraph, the quotation and the three essay bodies; left these three blocks hardcoded. All are verbatim `AboutMe.docx` — her words, not site furniture. | The file's stated reason for locking reaches the honours table and the two credential cards. It does not reach philosophy prose, welcome cards, or a lead sentence that makes no credential claim. |
| C2 | About — **clearing a Customizer text field silently restores the shipped copy** (`cas-about.php:543-563`) | An open field is open in both directions. | `cas_about_value()` applies the empty-string guard written for the image field to all five text fields. Emptying the quotation puts the old quotation back, with no message. | She cannot delete a paragraph she is allowed to rewrite, and nothing tells her why. Same shape on the Courses tier photos, where "Remove image" restores the shipped photograph. |
| C3 | About — **portrait alt text** hardcoded "Crystal Lau in her studio" | The photograph is hers, so its description follows it. | The photograph became swappable; the alt text did not. | Small, and only wrong once she uploads a different picture. One text field. |
| C4 | About — **a photo set falls back to the shipped photographs** until its first upload is published (`cas-about.php:279-284`) | — | A set with four uploads that all lack a featured image renders our stock photographs, with nothing on the page to say so. | The admin list warns per row. The page does not, so a half-finished upload looks like a working page. |
| C5 | Free Videos — **Crystal's portrait beside the featured heading** hardcoded (`cas-free-videos.php:626-645`) | Photographs are hers. | The same photograph is an editable Customizer image on About and a theme file here. Changing it on About leaves this one showing the old picture. | Exactly the failure the project already documented for the social links. Latent while no demos are published. |
| C6 | Free Videos — **closing band paragraph** (`:558-566`) and Showcase — **CTA band paragraph** (`cas-showcase.php:366`) | Body copy is hers. | Both hardcoded, while every other paragraph on their own pages is a Customizer field. The Showcase one additionally asserts a monthly-critique process nobody has confirmed. | Two Customizer textareas. The inconsistency is the real cost: adjacent paragraphs on one page, opposite rules. |
| C7 | Free Videos — the **courses link inside the demos introduction** (`:399`) | — | The default is computed at render time; the moment she edits that paragraph the URL is frozen into the database as raw `<a href>` markup she can see and break. | She is shown HTML in a plain textarea, and a slug change later rots the link with no warning. |
| C8 | Blog — **heading colour, alignment and HTML anchor discarded** (`cas_blog_sectionise()`) | Post appearance was deliberately left open in `theme.json`. | Every attribute on a top-level `<h2>` is dropped when the article is rebuilt into sections. She sets a colour, sees it in the editor, and it is not on the site. | The editor-says-one-thing failure this register exists to catch. |
| C9 | Home §3.4 — **About card alt text** hardcoded "Crystal — founder portrait in the twin-arch mask" (`cas-home-about.php:67`) | The photograph is hers, so its description follows it. | The photograph became a Customizer image; the alt text is fixed in code and overrides the media library's own. | Identical in shape to C3 on the About page, built in the same round that recorded C3. The Free Videos card next to it forces no alt at all and documents why — two cards, one row, opposite rules. |
| C10 | Login / Dashboard / Basket — **three lede sentences** hardcoded (`cas-account.php`) | Body copy is hers. | "Pick up exactly where you left off.", "Pick up where you left off — your bench is just as you left it." and "Review what's in your basket, then we'll take it from here." are literals with no setting anywhere in the module. | Same shape as C6. The counter-argument is on the record: DD §20 exempts these five pages from page-by-page treatment, so leaving them closed is defensible — it just was not written down as a decision. |
| C11 | Archives — **author heading prints the account login name** (`cas-archives.php`) | Item titles and the words on a page are hers. | `/author/cas_owner/` renders an `<h1>` of `cas_owner` under a branded "✦ WRITTEN BY" banner. It prints whatever the account's display name is, and `cas_owner`'s display name is literally `cas_owner`. | One field on one user profile fixes it, so this is nearer a launch task than a build defect — but it becomes visible the day her posts are published under that account, which is what HANDOFF §7 instructs. |

### Adjacent — not editability, found while comparing

- DD:775-777 requires the CTA to read `Add to Cart` before purchase and `View Course` after. `cas-course-single.php:94` emits "Add to cart" unconditionally; no purchase-state branch exists.
- DD:634-635 requires a member-terms clause stating that username and avatar may appear in student sharing. The testimonial cards do publish both (`cas-editable.php:235-236`), and the About terms accordion has one panel (`cas-about.php:625`) with panels 2-4 not implemented (`cas-about.php:599-605`).

## 3. THE UNDECIDED LIST

49 rows, up from 46. The homepage, account and archive build closed seven of the old rows —
five homepage sections that had nothing to edit now have fields — and opened ten new ones,
all of them small and all of them genuine silences rather than oversights. What is left is
still the same kind of thing: the document says nothing and Herman's Rule does not reach it.

| # | Area | What she currently cannot edit |
|---|---|---|
| U1 | **About page — the honours half only.** The prose and photographs were opened on 2026-08-23 (`cas-about.php:484-488` Customizer, `cas_about_photo` post type). §13 still never mentions a backend. | The honours table: every year, wording, certificate scan and caption (`:402-424`), which certificate carries the highlight (`:402`), the credential sentences naming SKY GALLERY, the Repin Academy and the Sun Museum (`:344,348`), the exhibition-poster row (`:440-444`), and the three **Demo scaffolding rows plus the visitor-facing note explaining them** (`:446-467`). Removing the scaffolding before launch is a code change. Left closed deliberately — see brief 1. |
| U2 | **Certificates (§14, frozen this round)** | Which certificate carries the highlight is the attribute `data-highlight` on one row (`cas-about.php:402`). Moving the highlight is a code edit. Adding a 2027 honour is a code edit. |
| U3 | **Homepage sections that do not exist** | **Five of seven built 2026-08-23.** Free Videos promo card (§3.3), About Crystal (§3.4), Shop All Courses (§3.5.1) with its course rail and three-part card (§3.5.2), Art Gallery card (§3.6), Blog & Tools (§3.7) with its blog rail — all built, all with their own rows above. Two things are still missing and both are now defects, not silences: the §5 New release carousel (defect A7) and the §3.5 Course Overview block. That last one is the live gap — about 1,100 words of her own copy from `assets/00 - Home/Under_Shopallcourses.docx`, styled in `cas-home.css` and emitted by nothing, so it currently renders on no page of the site. |
| U4 | **Home community band** (`cas-home.php:17,25,30`) | The paragraph is the only place on the homepage that states what the Fellow tier includes (Discord access). If the tier changes, the sentence, the image and its alt text all need a developer. |
| U5 | **Home testimonial card caption** (`cas-home.php:71`) | The literal text "Profile picture & username — from the member record" prints on every card as visitor-visible copy. Only a developer can remove it. |
| U6 | **Hero slide count** (`const CAS_HERO_SLIDES = 3`, `cas-hero.php:19`) | She can rewrite all three slides; she cannot add a fourth or drop to two. |
| U7 | **Course detail, the images** | DD:750 states five images per course; only the featured image renders (`cas-course-single.php:84`), the thumbnail strip is not implemented (`:10-12`). Four of five images per course have nowhere to go. |
| U8 | **Course detail, "Who it is for"** (`cas-course-single.php:59,190`) | Live and freely editable in Tutor today. DD:855-861 records that Crystal only asked a question about this section, so nobody has ruled on whether it should be open. |
| U9 | **Course detail, remaining** | The Media/Lessons/difficulty line (`:27-35,55`), the `Module`→`Phase` backend label, and refund text — **no refund copy renders on the course page at all**, so DD:817's placeholder mandate has no target there. |
| U10 | **Courses overview page furniture** | The filter and sort rail was built on 2026-08-23 and is derived from the published courses, so nothing there is editable by design. What remains: there is still no page intro, so the DD:656 Read More cut-point mechanism has nothing to truncate; the count wording, the alt-text template and the empty state are code. The missing FREE TRIAL band and page hero moved to Class A defect A4 — the document names both, so they are not undecided. |
| U11 | **Gallery gaps** | The locked introduction (DD:962, `cas-gallery.php:303`) promises "You can filter by medium, price, or date added" — no filters exist, and she may change neither the copy nor the fact. Medium/format chips, size line, was-price and the Sale ribbon have no field (`cas-gallery.php:11-14`). |
| U12 | **Blog appearance, and what sits around the article** | Both templates were built on 2026-08-23 (`cas-blog-templates.php`), so the whitelist now governs bodies that a page really displays. Two things stay unsettled. `theme.json` colour and typography controls are open (`cas-blog.php:15-22`) with no written ruling, and the article rebuild discards heading colour and alignment anyway — see C8. And §12 never described the article inner page (DD:1548-1550), so the prototype's author bio card and comment form were dropped with nothing on record either way. |
| U13 | **Login / Signup / Dashboard / Checkout** | **Built 2026-08-23, with no field opened.** The five surfaces now wear the site's banner, card and steps strip; Tutor still owns every form, the cart and the checkout. Nothing was granted to Crystal, and that stays undecided as a grant — decision brief 5 left the question of whether Tutor's own templates are acceptable to be settled separately. What is genuinely open: whether new accounts can be created at all (`users_can_register` is off, so the header's Sign up button leads to a closed card), whether Tutor's own dashboard panels and greeting are acceptable inside our chrome, and the membership tier chip the prototype drew and nothing on this install can populate. |
| U14 | **FAQ commercial answers** | Payment methods, refund window, access period, download limits, device requirements and failed-payment handling are DD:1271-1278 「不得自行推定」. They are editable as FAQ entries today with no content decided, so whatever is typed there becomes the site's answer. The "Ask me directly" sentence (`cas-pages.php:76-78`) is code. |
| U15 | **Header and footer chrome** | The cart badge is the literal `0` for every visitor and no admin screen changes it (`cas-header.php:85`). The footer tagline naming SKY GALLERY appears on every page (`cas-footer.php:79`), as does the copyright line (`:104`), and the header logo alt text (`:79`) stays fixed even when the logo image is swapped. |
| U16 | **Scope of the four social settings** | DD:506-511 grants them inside the Home ABOUT CRYSTAL card. One global value now drives the footer (`cas-footer.php:98-100`), the FAQ contact line (`cas-pages.php:76-78`), the About channel list and mailto (`cas-about.php:534,677`) and the home social strip. The document neither extends the grant to those places nor scopes it to one. |
| U17 | **Archives and search** | Built 2026-08-23, so the category, tag, date, author and search pages are no longer raw hello-elementor. Three things nobody has ruled on. There is **no search field anywhere on the site**, so a visitor whose search finds nothing has no way to search again except by editing the address. Search returns WordPress **Pages** as well as articles, chipped with the word "Page" — searching "log" returns the homepage as a card. And the **404 page** is now the last stock hello-elementor surface on the site, because the archive module deliberately declines `is_404()` so that an empty date archive keeps its 404 status instead of being answered with an apology at HTTP 200. |

---

## DECISION BRIEFS — 17 undecided areas

Six of the seventeen have now been acted on. Their briefs are kept intact with a status line
at the top, so the reasoning that produced the decision stays readable next to what was done:
brief 1 (About), brief 3 (the homepage), brief 4 (Blog), brief 5 (Showcase, Free Videos and
the account pages), brief 8 (Courses filters and tiers) and brief 17 (archives).

Ordered by ongoing developer burden. Top = every change routes through a deploy, and changes will be frequent. Bottom = nobody will realistically touch it.

Each brief answers: what it is · how often it changes · cost of opening · cost of leaving closed · recommendation (a recommendation only — the call is yours).

---

### 1. U1 — The About page, in full

**Status 2026-08-23: the recommendation below was taken.** (a) prose and (b) photographs are
open — five Customizer fields under Pages → About, and a `cas_about_photo` post type for the
three carousels. (c) the honours table stays closed, for the reasons this brief gives. Three
prose blocks were missed and are now Class C defect C1.

**What.** `cas-about.php` is 1,151 lines of hardcoded PHP. Its own header states the position: "Chapter 13 … is SILENT on which fields the author may edit, so the default-closed rule in 1.4.14 applies to the whole page and every word here is hardcoded" (`cas-about.php:5-8`). There is no Customizer section, no post type, no meta for anything on this page. Locked: her biography and every essay panel (`:199-203,269-277,292,310-313,484-529`), her portrait (`:189`), the six live-drawing photos (`:150`), the exhibition and Artful Living carousels (`:295,301`), every honour row and caption (`:402-424`), and the sentences naming SKY GALLERY, the Repin Academy and the Sun Museum (`:344,348`).

**Change frequency.** Higher than a static-prose page normally is, for three reasons. It is autobiography, and she is still supplying the source (`docs/20260807_About_Copy_Provenance.md` exists because provenance was contested). It carries live credentials — a new exhibition or selection lands one to three times a year, judging by her 2016/2017/2025/2026 rows. And it carries the assets she is actively replacing. Realistically: a handful of edits before launch, then several per year, forever.

**Cost of opening.** Not one job — three, with very different prices. (a) Prose panels as editable fields: cheap, roughly half a day, and near-zero visual risk since each panel is a plain text block. (b) Photos as a media field per slot: cheap, and the risk is aspect-ratio drift unless the slots crop like the hero's do. (c) The honours table as a repeatable post type with year, wording, scan, caption: the expensive one, one to two days, and it carries the real hazard — this is the page where an earlier build **invented credentials that had to be removed** (`cas-about.php:12-14`). An open honours table is an open door to a claim nobody checked, and to an unmasked certificate scan being uploaded over a deliberately pixelated one (`:394-399`).

**Cost of leaving closed.** A typo in her own biography is a deploy. A new honour is a deploy. Every photo swap is a deploy. This is the single largest source of "can you just change…" traffic on the site.

**Recommendation.** Split it. Open (a) prose and (b) photos — low cost, high traffic, low risk. Leave (c) the honours table closed for now and revisit after launch; the fabrication and masking risks are real, and the frequency (a few per year) does not justify the build or the exposure. Ask her before building anything: chapter 13 never asked for a backend, so this is a new request, not a defect being repaired.

---

### 2. U7 — Course detail: four of the five images per course have nowhere to go

**What.** DD:750 states five images per course. Only the featured image renders (`cas-course-single.php:84`); the thumbnail strip is explicitly not implemented, with the reason stated in code — "Both need data the import does not carry, and inventing it would be worse than leaving the sections out" (`cas-course-single.php:10-12`).

**Change frequency.** Per course, at course creation, plus whenever she reshoots. There are four courses now and the whole business model is adding more. This is not a one-off — it recurs every time the catalogue grows.

**Cost of opening.** Moderate: a repeatable image field on the course post type plus the `#co-thumbs` strip, roughly one day. Risk is low if the slots crop like the hero's, because four thumbnails at a fixed ratio tolerate any upload. Risk is real if they do not — four images of four aspect ratios in a row is exactly the composition problem the Phase 2 line was drawn around.

**Cost of leaving closed.** The course detail page ships at four-fifths of its specified content, and every future course launch needs a developer to complete it — which puts you on the critical path of her selling anything new.

**Recommendation.** Build it, with crop-to-slot containers, before launch. The frequency is tied to revenue, the spec already calls for five images, and crop-to-slot removes the composition risk. This is the highest-value item in the list per pound spent.

---

### 3. U3 — Seven homepage sections that do not exist

**Status 2026-08-23: five of the seven were built.** Free Videos card, About Crystal, Shop
all courses with its rail and three-part card, the Art Gallery card and Blog & Tools all
exist as `inc/cas-home-*.php` shortcodes on the homepage, in the chapter's own order. Each
returns an empty string rather than a heading over a blank grid while its content is
unpublished, which is the state the course rail and the blog rail are in today. Defect A1
closed with them. Two things did not land: the §5 New release carousel, now defect A7, and
the §3.5 Course Overview block, which is still the item below. The blocker this brief named
is unchanged — the courses are drafts, so the rail renders nothing.

**What.** Below the hero the homepage is: community band, testimonials, social strip (`cas-home.php:15,52,91`). Missing: Free Videos promo card (§3.3), About Crystal (§3.4), Shop All Courses intro (§3.5.1), the three-part course card (§3.5.2), Art Gallery card (§3.6), Blog & Tools (§3.7), and the course and blog rails (`cas-home.php:5-6`, whose comment gives the reason: "they need published content, and there is none yet").

**Change frequency.** The sections themselves are near-static once built. But three of them are rails that must track published content continuously, and §3.4's About image is a field DD:506-507 explicitly opens — so one missing section is also Defect A1.

**Cost of opening.** This is not "opening a field", it is building the homepage. Several days. The blocker is stated and is not technical: the courses are drafts because titles and prices are unapproved placeholders, so the rails have nothing to render. Risk once built is low; these are template sections fed by post data.

**Cost of leaving closed.** The homepage does not do its job. It never mentions the gallery, the blog, the free videos or a single course. Every visitor arrives, sees a hero and three quotes, and leaves. This is not a support-burden cost, it is a revenue cost.

**Recommendation.** Build it — but the decision you actually need to make first is unblocking the course titles and prices with Crystal, because three of the seven sections cannot be built without them. Treat that sign-off as the critical-path item, not the code.

**Still outstanding after the build.** The Course Overview block is the one to settle next,
and it needs a decision before it can be built honestly: its two ledes and two numbered
principles are body copy, so Customizer fields, but its four self-answered questions can grow
in number, so they are a post type. That is two different mechanisms in one block. The copy is
hers and already written, so nothing has to be invented — it just cannot be built without
choosing.

**DECIDED 2026-08-24 (issue #26).** The split recommended here was taken: the two ledes and
two principles are six Customizer fields under a new "Home — Course overview" section
(`cas_home_overview_lede_1/2`, principle 01/02 title and text), and the four questions are a
`cas_overview_qa` post type (title + content, menu_position 25, free on the ladder). The
heading, numerals and section structure stay locked under Herman's Rule. The "lifetime access"
sentence remains withheld under §7.4. Rows recorded above as OK with this decision as
`decided_by`.

---

### 4. U12 — Blog: the fence is built, the field is not

**Status 2026-08-23: built.** Both templates exist (`inc/cas-blog-templates.php`), the list
page carries an editable introduction, and articles carry a topic, a reading time and the
Developer's-note mechanism. The palette stays open as decided. Two new items came out of the
build: heading colour and alignment are discarded on the front end (C8), and the author bio
card and comment form were not ported (still undecided, DD:1548-1550).

**What.** `cas-blog.php` renders nothing — it is only the block whitelist (`:11-12`, "this file is not a page template and renders nothing"). There is no blog list template and no single-post template in the theme. So the whitelist currently governs post bodies that no page displays. Separately, `theme.json` colour and typography controls are left open with no written ruling (`cas-blog.php:20-22`).

**Change frequency.** The highest on the site, by design. Blogging is the one activity DD §12.2 hands to her outright, and she has already written three real posts (`docs/blog-drafts/`).

**Cost of opening.** Two templates, one to two days. Near-zero editorial risk — the whitelist already excludes Custom HTML and Table for stated reasons, and the block set is small. The one genuinely open question is the palette: with neither Block Patterns nor palette limits, nothing stops post-to-post visual drift, and that was chosen knowingly.

**Cost of leaving closed.** She cannot publish. The one thing she was promised she could do alone, she cannot do at all.

**Recommendation.** Build both templates before launch — this is a missing deliverable, not an editability question. Leave the palette open as already decided; if drift appears after ten posts, adding palette limits is a small change, not a rebuild.

---

### 5. U13 — Four pages that do not exist, and links that hide it

**Status 2026-08-23: Showcase and Free Videos are built**, both to the recommendation below.
Each has its own post type and its own admin screen; Free Videos also has four Customizer
fields. Both pages are empty because no content has been supplied, not because they are
unbuilt.

**Login / Signup / Dashboard / Checkout were also built, later the same day, to the second
half of the recommendation.** The site's banner, card and checkout steps strip now wrap
Tutor's own login form, cart and checkout; a new `/login/` page holds the designed entry
point, and the signup page answers with an honest closed card instead of Tutor's "Access
Denied" screen while registration is off. **No field was opened to Crystal on any of them**,
and that is the part to keep straight: the shell exists, the grant question does not. Three
lede sentences were hardcoded in the process and are now Class C defect C10.

**What.** No template exists for Showcase, Free Videos, Login, Signup, Dashboard or Checkout. The footer links to `showcase` and `free-videos` (`cas-footer.php:91,86`) and the header links to `checkout` (`cas-header.php:83`). Note precisely: DD §11 (Showcase) and §15 (Free Videos) are **not** silent chapters — both specify headings, section structure, ratios and content sources in detail. What neither says is which fields the backend opens. So these are unbuilt pages with a written design, not undesigned pages.

**Change frequency.** Free Videos is a content rail she will feed continuously — four blocks, per §15, fed by her YouTube uploads and site-hosted files. Showcase is student work, which by nature changes constantly.

**Cost of opening.** Substantial — these are pages, not fields. Free Videos in particular needs a video post type with a section taxonomy and two aspect ratios (§1.4.12). Risk is moderate: §15's own 待確認 list says the third section still has no agreed name and the cover images and URL/ratio table have not been supplied.

**Cost of leaving closed.** Worse than nothing, because of the trap below: `cas_page_url()` falls back to the homepage for a missing page (`cas-footer.php:62-66`), so those footer links look like they work. Nobody will report them.

**Recommendation.** Build Free Videos and Showcase — both are specified, both are content pages she feeds, and the §15 open items are questions for her, not for you. For Login/Signup/Dashboard/Checkout, decide separately whether Tutor's own templates are acceptable; DD §20 lists those pages as never having been reviewed page by page, so there is no design to miss.

---

### 6. U11 — The Gallery promises filters it does not have

**What.** The locked introduction (`cas-gallery.php:303`) reads: "You can filter by medium, price, or date added." No filters exist. Her copy is quoted in the direction at §9.1 with the instruction 不改寫 — do not rewrite — and was once wrongly deleted as invented, so the code comment forbids touching it. Separately, the medium/format chips, size line, was-price and Sale ribbon are not rendered, because §9.3 opens exactly seven fields and those are not among them (`cas-gallery.php:11-14`).

**Change frequency.** The intro is one sentence and changes never. The gallery contents change constantly — which is the point: the more artwork she publishes, the more the missing filter matters.

**Cost of opening.** Two separate things. Filters: the markup already carries the data attributes for it, but there is no medium field to filter on, so this means adding fields §9.3 does not open plus the UI — one to two days. Changing the sentence instead: free, but requires her permission, because it is her copy under an explicit do-not-rewrite instruction.

**Cost of leaving closed.** A visible false statement on a commercial page, on every visit, forever.

**Recommendation.** Do not build the filters yet. Take the sentence to her — "the filters are a later addition; may I cut that clause, or shall I build them?" — because it is one message and it settles both branches. This is the cheapest item on the list to resolve and one of the most visible if left.

---

### 7. U2 — Which certificate is highlighted is an HTML attribute

**What.** One `data-highlight` attribute on one `<figure>` (`cas-about.php:402`) decides which certificate appears large above the rest. The code is unusually explicit that this is unresolved: the highlight sits on the 2026 Accademia certificate "because that is the most recent and most widely recognised … and something had to be picked to build against. **Her note may instead have meant a control that lets her pick the highlight from the CMS, which is a different build. Both still to put to her.**"

**Change frequency.** Rarely — when she wins something more impressive, or changes her mind about which credential leads. Once a year at most.

**Cost of opening.** Small if it is a single "which one is featured" checkbox on an honours post type — but that only exists if U1(c) is built first. As a standalone it is not worth building.

**Cost of leaving closed.** One email and one deploy per year.

**Recommendation.** Do not build a control. Ask her the question the code already flags — did she mean "highlight this one" or "let me choose"? If the former, you are done and nothing needs building. That question is unanswered and it is cheaper to ask than to guess in either direction.

---

### 8. U10 — Courses overview page furniture

**Status 2026-08-23: the filter and sort rail was built**, ahead of the recommendation below,
together with the THREE WAYS TO STUDY tier block that closed defect A2. The rail derives every
chip from the published courses and offers a group only where it can change the result, so
nothing in it is editable and nothing in it needs to be. The page intro is still not built.
The missing hero and FREE TRIAL band became defect A4.

**What.** No page intro exists, so DD:656's Read More cut-point mechanism has nothing to truncate. No FREE TRIAL block. No filter or sort controls — deliberately deferred, with the reason in code: "They are a larger piece and belong with real courses to filter" (`cas-courses.php:14-16`). The card count wording, the alt-text template and the empty state are hardcoded (`:151,153,101`).

**Change frequency.** The intro is prose that changes once or twice a year. The controls are structural and change never once built.

**Cost of opening.** The intro as an editable field: trivial, an hour, and the Read More mechanism DD:656 asks for makes it safe against over-long text. The filters: one to two days, and genuinely premature at four courses.

**Cost of leaving closed.** Low while the catalogue is small. It grows with the catalogue.

**Recommendation.** Open the intro now — it is cheap, it is prose, and DD:656 already specifies the truncation that protects the layout. Leave filters closed until there are enough courses to filter; the code's stated reason is sound.

---

### 9. U9 — Course detail: chips, the `Module`/`Phase` label, and missing refund copy

**What.** The runtime/lesson-count/difficulty line is derived, not typed (`cas-course-single.php:27-35`) — DD:793 settles that and it is correct. The backend field label rename from `Module` to `Phase` (DD:852, 1541) is not applied. And **no refund copy renders on the course page at all**, so DD:817's placeholder mandate has no target there.

**Change frequency.** The chips: never, correctly. The refund text: once, then whenever terms change. The label: once.

**Cost of opening.** The label rename is a small filter, under an hour. The refund clause is not an editability problem — it is a missing content decision that also blocks About's terms panels (see U-adjacent, brief 16 territory). Writing it costs nothing; getting it right requires her or her adviser.

**Cost of leaving closed.** The refund gap is a launch blocker, not a support burden. Selling a course with no stated refund terms is a commercial exposure, and DD:1271-1278 explicitly forbids inventing them (不得自行推定).

**Recommendation.** Treat the refund clause as launch-blocking and chase the content, not the code. Do the `Module`→`Phase` rename — the direction asks for it twice and it is an hour. Leave the chips derived.

---

### 10. U8 — "Who it is for" is open, and nobody ruled on it

**What.** `_tutor_course_target_audience` is live and freely editable in Tutor today (`cas-course-single.php:59,190`). DD:855-861 records only that Crystal **asked a question** about this section — §7.6 exists because the original document omitted it entirely. So nobody has ruled on whether it should be open; it is open by accident of Tutor's defaults.

**Change frequency.** Per course, at course creation. Low thereafter.

**Cost of opening.** Zero — it is already open.

**Cost of closing.** Small, but it would take away something that currently works and that she plausibly wants.

**Recommendation.** Ratify it as open and write it down. The distinction that matters here is the one this whole exercise exists to preserve: "open because we decided" and "open because Tutor happened to expose it" look identical in the admin and are completely different in fact. One line in the register converts the second into the first.

---

### 11. U14 — The FAQ answers nobody has decided

**What.** FAQ entries are fully editable (`cas-editable.php:132-152`) and DD:1264 explicitly grants that (問答內容可編輯). The problem is the opposite of locked: DD:1271-1278 lists seven things that **must not be assumed** — payment methods, whether a card works without a PayPal account, the refund window, the access period, download limits, device requirements and payment-failure handling. Whatever is typed into those FAQ entries becomes the site's answer. Per CLAUDE.md 2026-08-23, the FAQ was re-seeded verbatim from her `.docx`, which carries both the "lifetime access" phrase (§7 requires her confirmation before it appears anywhere) and the full PayPal guest-checkout claim that §16 says is a **checking request addressed to Herman, not copy** — and both are live now.

**Change frequency.** Rarely, but each change is commercially binding.

**Cost of opening.** Already open. The cost is the reverse: no review gate stands between her typing and the site making a payment promise.

**Cost of leaving as-is.** Two claims are published today that the direction document says may not be published unverified.

**Recommendation.** This is the most urgent item on the list and it is not a build. Verify the nine PayPal points for HK and UK, get her confirmation on "lifetime access", and correct or pull those two entries. Leave the field open — DD grants it — but do not let an open field stand in for a decision that DD says you owe her.

---

### 12. U4 — The Fellow-tier paragraph on the homepage

**What.** `cas-home.php:30` is the only place on the homepage that states what the Fellow tier includes ("Crystal's private Discord channel"). The heading, eyebrow and button are locked by DD:313. The paragraph, the image `img/Rubber.jpg` and its alt text are all hardcoded and named by no chapter.

**Change frequency.** Rarely — but it changes exactly when tier pricing or contents change, which is a moment when getting it wrong costs money.

**Cost of opening.** Trivial: one Customizer textarea plus one image field, under an hour. Risk is a long paragraph pushing the band's flex layout out of shape, since it sits in a fixed-height row beside a 160px image.

**Cost of leaving closed.** One deploy each time the tier definition changes. Perhaps once or twice ever.

**Recommendation.** Open the paragraph, leave the image and alt closed. The paragraph is body text, which DD:311 lists as generally open, and it is the sentence most likely to become untrue. The image is a decorative photo nobody will ask to change.

---

### 13. U6 — The hero is fixed at three slides

**What.** `const CAS_HERO_SLIDES = 3` (`cas-hero.php:19`). She can rewrite all three slides' titles, descriptions and both images — those are fully open and correctly so, per DD:468-472. She cannot add a fourth or drop to two.

**Change frequency.** The slide *contents* will change often, and already can. The slide *count* — realistically never; DD §3.2 says 保留三張 Slides, three slides, as a design decision.

**Cost of opening.** Moderate and disproportionate: a variable-count carousel needs repeater UI in the Customizer (which the Customizer is bad at), and the counter markup currently hardcodes a leading zero (`cas-hero.php:196`) so a two-digit count would print "/ 010".

**Cost of leaving closed.** Effectively nil. The document specifies three.

**Recommendation.** Leave closed. This is the clearest "locked by an actual decision" row in the register — DD §3.2 names the number.

---

### 14. U15 — Header and footer chrome

**What.** Three separate things. The cart badge is the literal `0` for every visitor, with no admin screen and no live count (`cas-header.php:85`). The footer tagline "artist and founder of SKY GALLERY" prints on every page (`cas-footer.php:79`) — a factual credential claim. The copyright line is generated from the current year (`:104`), which is correct and needs nothing. The header logo alt text stays "Lau. — Crystal's Art School" even after she uploads a different logo (`:79`).

**Change frequency.** The tagline: when her affiliation changes. The badge: it should change on every add-to-cart, and does not.

**Cost of opening.** The tagline as a field: trivial. The badge is not an editability question at all — it is unfinished cart wiring, and belongs with the Weeks 7-8 payment work.

**Cost of leaving closed.** Low for the tagline. The badge is a visible defect: a shopper adds a course and the header still says 0.

**Recommendation.** Leave the tagline closed — it is one line, it changes almost never, and it is a factual claim worth having reviewed rather than typed. Move the cart badge onto the payment-work list where it belongs; do not carry it as an editability item.

---

### 15. U5 — The testimonial card prints its own instructions

**What.** Every testimonial card renders the literal text "Profile picture & username — from the member record" (`cas-home.php:71`). That sentence is a note to whoever configures the card. It is currently visitor-facing copy on the homepage.

**Change frequency.** Once — it should be deleted.

**Cost of opening.** Not applicable; nobody needs to edit this, it needs removing.

**Cost of leaving closed.** It ships.

**Recommendation.** Delete it before launch. This is not an editability decision, it is a launch-blocker hiding in the undecided pile — flagging it here so it does not get filed as "locked, fine".

---

### 16. U16 — The four social settings are used more widely than the grant

**What.** DD:506-511 grants Instagram, Facebook, YouTube and email as editable **inside the Home ABOUT CRYSTAL card**. One global value now drives the footer (`cas-footer.php:98-100`), the FAQ contact line (`cas-pages.php:76-78`), the About channel list and its mailto (`cas-about.php:534,677`), and the homepage social strip. The Customizer panel says so plainly: "Changing one here changes all three" (`cas-editable.php:52`). The document neither extends the grant to those places nor scopes it to one.

**Change frequency.** Rarely. But note DD:512 separately requires the homepage About, Follow Along and Footer to use the **same** four channels — so a single shared value is what the document asks for in substance, even though the editability grant is written narrower.

**Cost of opening.** Zero. It is already this way, and the code comment explains why: "a hardcoded copy would go on using the old one after she changed it" (`cas-about.php:172-174`).

**Cost of leaving as-is.** Zero in practice. The only cost is documentary — an undocumented broadening of a grant is how a register stops being trustworthy.

**Recommendation.** Ratify the current behaviour and record it. DD:512 already requires the three areas to match; the shared value is the mechanism that guarantees it. This is a paperwork fix, not a code change.

---

---

### 17. U17 — Archives, search, and the last unstyled page

**Status 2026-08-23: built.** The category, tag, date, author and search pages are no longer
raw hello-elementor — each carries the wood banner, a derived heading, the blog card grid and
a Newer/Older pager. Nothing on them was opened to Crystal, and nothing on them should be:
every string is either a structural label or a measurement of the query in front of the
reader. Three questions came out of the build and none of them is an editability question.

**What.** There is **no search field anywhere on the site** — not in the header, not on the
results page, and the prototype never drew one. A visitor who mistypes a search gets an
honest empty state and no way to try again. Search also returns WordPress **Pages** beside
articles, chipped with the word "Page", so searching "log" returns the homepage as a card and
"checkout" returns a page nobody can open. And the **404 page** is now the only stock
hello-elementor surface left on the site.

**Change frequency.** Never, for all three. These are structural decisions, not content.

**Cost of opening.** The search field is a new component — the design system has no styled
input to copy, so it is a small design job rather than a port, and it belongs in the header
if it exists at all. Excluding Pages from search is one `pre_get_posts` filter. Styling the
404 is one more template through the same shortcode that already serves the archives.

**Cost of leaving closed.** The search field is the one that costs something real: a search
that finds nothing is a dead end, and search is reachable from any address bar. The Pages
issue is cosmetic until the four courses are published, at which point a course starts
appearing in blog-card layout with a reading time computed from its description. The 404 is
seen rarely and by nobody who matters.

**Recommendation.** Style the 404 — it is the cheapest of the three and it closes the "last
unstyled page" line for good. Add the `pre_get_posts` filter before the courses are
published, not after. Leave the search field until somebody asks for it: adding a search box
to a header that was designed without one is a design change, not a repair.

## TRAPS

Neither defects nor undecided areas. These are places where **a field the document opens feeds something the layout or the site's correctness depends on**, and a reasonable edit by the client visibly breaks the page.

| # | Trap | Where | What a bad edit does |
|---|---|---|---|
| T1 | **Removing a hero image blanks the `src`.** `cas_brand_image()` guards with `$set ? $set : fallback` (`cas-brand.php:49`). `cas_hero_value()` does not — it is `get_theme_mod(key, default)`, and a Customizer "Remove image" stores `''`, which `get_theme_mod` returns in preference to the default. | `cas-hero.php:95` vs `cas-brand.php:49` | `<img src="">` on the homepage hero — the browser requests the page as an image and shows a broken graphic. The same action on the logo is safe. The inconsistency is the trap: identical UI, opposite outcomes. |
| T2 | **The hero title is the page `<h1>` and a free-text textarea.** Slide 1's title becomes the h1; slides 2-3 become `<p>` (`:165-171`). Font size is `clamp()` over a fixed-height stage, and line breaks are deliberately preserved. | `cas-hero.php:165-173` | A four-line title overflows the stage; a blanked slide-1 title leaves the homepage with an empty `<h1>`. DD:469 opens this field. |
| T3 | **Testimonials have no cap, but the direction fixes three.** DD:617 says 固定顯示三則. `cas_testimonial_items()` passes `numberposts => -1` and `cas_render_testimonials()` loops all of them with no slice. | `cas-editable.php:219` + `cas-home.php:65` | Publishing a fourth testimonial silently changes a three-card grid into four. The register row reads "Which three show" — nothing enforces three. |
| T4 | **A testimonial with no member selected renders an empty name.** `name` and `avatar` come back `''` when `_cas_user_id` is 0 or the user is deleted. The card still prints its caption claiming the name comes from the member record. | `cas-editable.php:230-237`, `cas-home.php:68-71` | An empty `<strong>` above the words "Profile picture & username — from the member record". No admin warning; the meta box defaults to "— select a member —". |
| T5 | **Prices print as raw floats.** `esc_html( $sym . $reg )` where `$reg = (float)`. | `cas-courses.php:122-124`, `cas-course-single.php:86-90` | A price of 58.50 renders as **£58.5**. 58.00 renders as £58. Price is an open field on a commercial page; the money is displayed wrong the moment she enters pence. |
| T6 | **Course catalogue order runs backwards relative to every other Order box on the site.** Courses: `'orderby' => 'menu_order date', 'order' => 'DESC'` — DESC applies to both keys. FAQ, testimonials and artwork all use `menu_order ASC`. | `cas-courses.php:142-143` vs `cas-editable.php:138,223`, `cas-gallery.php:278-281` | She sets Order on a course expecting the Gallery's behaviour and gets the reverse. Same field name, same admin box, opposite direction. |
| T7 | **The nav menu silently drops sub-items and has no fallback.** `depth => 1` and `fallback_cb => false`, on both the desktop menu and the mobile sheet. | `cas-header.php:56-57,62-71` | A sub-item added under Courses in Appearance > Menus renders nothing at all. Deleting or unassigning the `cas-primary` menu renders **no navigation on any page** — no fallback list appears. The nav is editable (Defect B3); this is what a bad edit costs. |
| T8 | **The cart link has no fallback.** `get_permalink( get_page_by_path( 'checkout' ) )` returns `false` when no page has that slug; `esc_url(false)` is `''`. | `cas-header.php:83` | Renaming or trashing the checkout page turns the header cart into `href=""`, which reloads the current page. Contrast `cas_page_url()`, which does fall back — again, two helpers, opposite failure modes. |
| T9 | **Footer links to missing pages land on the homepage instead of 404ing.** `cas_page_url()` returns `home_url('/')` for any slug with no page. | `cas-footer.php:62-66` | "Student showcase", "Free videos" and "Blog" all currently point at the homepage and look like working links. This is why U13 will never be reported by anyone testing the site. |
| T10 | **Two currencies, one site.** Course prices use Tutor's configured symbol via `cas_course_currency()`. Gallery prices hardcode `&pound;` on the card, in the meta box label and in the admin column. | `cas-gallery.php:421` (+ meta box `:183`, column `:254`) vs `cas-courses.php:46-48` | Changing the Tutor store currency moves the course prices and leaves every painting priced in pounds. Currency is an open setting (Defect B1). |
| T11 | **An artwork with no featured image vanishes silently.** `cas_artwork_card()` returns `''`, and the loop `continue`s without incrementing the index. | `cas-gallery.php:337-340`, `:399-405` | She publishes a painting, forgets the featured image, and it simply is not on the page — with no empty-state message, because `$index` is non-zero from the other cards. (Mitigating: the Etsy admin column *does* warn "Missing — no Buy button" for a missing link. The image has no equivalent warning.) |
| T12 | **The course description feeds two different layouts.** It renders as the hero's `.lead` (`cas-course-single.php:103`) and, per DD:240 and 884-885, is also the lesson page's image-and-text body. It passes through `wpautop( wp_kses_post( … ) )`, so headings, images and lists all survive. | `cas-course-single.php:103` | Text written to fill a lesson panel overflows the hero's right column, and vice versa. One open field, two conflicting length requirements, and rich blocks permitted in both. |
| T13 | **One Logo2 upload renders in two placements with different alignment rules.** DD §1.4.5 keys alignment off whether a page has a wood-grain banner: centred on the homepage (`is-center`), right-aligned on the six banner pages. Both read the same `cas_logo2` theme mod. | `cas-hero.php:148` vs `cas-pages.php:30` | An upload with a different aspect ratio or padding is judged against two different alignments at once. The placement rule that governs it is a document decision she cannot see from the Customizer, which offers one field labelled "Hero signature mark". |
| T14 | **The FAQ page has no empty state.** Courses print "No courses are published yet."; the Gallery prints "No artwork is published yet." The FAQ loop prints nothing. | `cas-pages.php:58-68` vs `cas-courses.php:151`, `cas-gallery.php:339` | Unpublishing or trashing the FAQ entries leaves the heading "Frequently asked questions" over an empty box. FAQ content is explicitly open (DD:1264), so this is reachable by ordinary use. |
| T15 | **A free video saved without a section is invisible, and the admin says otherwise.** `cas_fv_films()` filters on the meta key, which is an inner join, so a row with no `_cas_free_video_section` matches no section. The list column defaults the empty value and prints "Step-by-step demos". | `cas-free-videos.php:355-359` vs `:474-491` | Reachable over REST, WP-CLI or any importer — not through the block editor, which always posts a value. The film is on the Free videos list, labelled with a section, and on no page. |
| T16 | **A section whose films all lack a cover photograph reports itself empty.** `cas_fv_card()` returns `''` with no thumbnail, and the section then takes the empty-state branch. | `cas-free-videos.php:660-663`, `:539-543` | Three published films with no covers produce exactly the page zero films produce: "Nothing published in this section yet." Only the admin Cover column says otherwise. The same skip-if-no-image rule applies to Showcase pieces. |
| T17 | **A blog post with no featured image leaves a 601px empty column.** The feature card is a two-column grid whether or not the image half renders. | `cas-blog-templates.php` + `industrial-2.css:905` | Measured live: `.bf-body` 734.8px beside 601.2px of nothing. A featured image is optional in WordPress and nothing on the Posts screen says it is required here. |
| T18 | **Reading time and the ribbon type size are both computed from bytes, not characters.** `str_word_count()` returns 0 for Chinese; `strlen()` over-counts a Chinese category by three. | `cas-blog-templates.php` | A Chinese article always reads "1 min" on every card and in the byline. A Chinese category shrinks the feature ribbon's type to its 14px floor. Chinese versions of her posts are expected. |
| T19 | **The Posts screen offers Tags, and nothing on the blog reads them.** Topics come from Categories only, and the default "Uncategorized" term is skipped. | `cas-blog-templates.php` | A topic typed into Tags reaches no chip, no card and no ribbon. Nothing on the editing screen says which box is the live one. |
| T20 | **Emptying an open text field silently restores the shipped copy.** The empty-string guard written to stop a blank image `src` is applied to text fields too. | `cas-about.php:543-563`, `cas-courses.php:500-505` | She deletes the About quotation, presses Publish, and the old quotation comes back with no message. "Remove image" on a Courses tier photo behaves the same way. Safer than a blank page, and indistinguishable from the field not working. |
| T21 | **The wall order is a number she cannot see on the list screen.** Showcase order is `menu_order ASC, date DESC`; the Student work list sorts by date and has no Order column, and the Customizer pointer says "Drag to reorder the wall". | `cas-showcase.php:273-276`, `cas-customizer-map.php:69` | There is no drag ordering anywhere in this install. She is sent looking for a control that does not exist, and the number that actually decides the order is only visible inside each piece. |
| T22 | **Publishing a course while no artwork is published leaves a 300px hole.** `.shop-layout` is a fixed two-column grid — `minmax(0,1fr) 300px` — and the Art Gallery card is its second column. The card correctly renders nothing until an artwork is published. | `industrial-2.css:336` + `cas-home-gallery.php` | The homepage Shop row shows the course rail beside 300px of empty page. Unreachable today because both halves are empty; reachable the moment she publishes a course before publishing a painting, which is the likelier order. Collapses to one column below 860px, so it is desktop-only. |
| T23 | **The TOOLS card appears before the topic it links to exists.** The Blog & Tools block renders as soon as any published post carries a featured image; the TOOLS card's destination is the Blog page filtered to the `Tools` topic, and nothing checks that the topic exists. | `cas-home-blog.php` | She publishes her first article, files it under Practice, and the homepage gains a TOOLS card leading to a Blog page showing its filter-empty state. Correct per §3.7, and it looks broken. |
| T24 | **Two homepage cards handle a missing picture in opposite ways.** The About card returns nothing at all when its photograph is gone; the Free Videos card next to it still renders, with no `<img>`, over its own dark veil. | `cas-home-about.php` vs `cas-home-free-videos.php` | Deleting the shipped Free Videos photograph from the media library — not through the Customizer, which falls back correctly — leaves a titled card with no picture. Small, and the same shape as T1: identical-looking cards, opposite outcomes. |

**One non-trap worth separating out:** `cas_about_livedrawing_decks()` calls `shuffle()` on every page load (`cas-about.php:150-158`), so the About page's live-drawing carousel is non-deterministic between requests. That is intentional scaffolding while only six photographs exist, and the code says so — flagging it only so a future reader does not file it as a caching bug.
---

## HERMAN'S RULE (2026-08-23) — the default that settles the UNDECIDED rows

Stated by Herman and binding for every page built from here:

> **Structure and headers are mine. Pictures and texts — mostly course content — are the
> client's.**

This is a general default, not a per-field grant. It does not override a chapter that says
otherwise; it fills the silence the register found in 54 places.

| Herman's — LOCKED | Crystal's — OPEN |
|---|---|
| Page and section structure, order of sections | Body copy and paragraphs |
| Section headings and eyebrows | Item titles (a course, an artwork, an article, an FAQ) |
| Button labels and their destinations | Photographs and every image |
| Icons, colours, fonts, spacing | Prices, links, tags and their values |
| Breakpoints and layout behaviour | Anything that is one row in a list she maintains |

**How to apply it when building:**

1. If the direction document's chapter names the field, that wins — grant or lock as written.
2. Otherwise apply the rule above. A field that is *content* becomes editable; a field that
   is *chrome* stays in code.
3. Where a thing can grow in number — artworks, articles, FAQ entries, videos, testimonials —
   it is a post type, never a fixed set of Customizer slots. Fixed slots are how a CMS ends
   up with "FAQ 11" living in the footer.
4. Where there is exactly one of a thing — a logo, a contact email, a page's hero image —
   it is a Customizer field under that page's section.
5. Images always crop to their slot (`object-fit: cover` on a fixed `aspect-ratio`), so a
   swapped picture can never displace the composition. This is what makes opening an image
   safe rather than a support liability.

Rows previously marked `UNDECIDED` because the document was silent are decided by this rule
from 2026-08-23. Herman reviews and may amend; the rule is the default, not the last word.

**Applied on 2026-08-23.** The Showcase, Blog and Free Videos pages were built to it, and the
About prose and photographs and the Courses tier fields were opened under it. Those rows now
read `decided_by = Herman's Rule 2026-08-23` and `status = OK`. Where the build did not follow
the rule, the row reads `DEFECT` and appears in Class C above — the rule is the yardstick, so
missing it is a defect, not a silence.

**Applied again the same day**, to five homepage sections, the five account surfaces and the
five archive surfaces. Points 3 and 4 of the rule did the most work there: the course rail,
the blog rail and the art gallery card all read post types that already existed rather than
growing a second store of their own, so a picture is changed where the thing itself is edited.
Point 5 held everywhere — every new image slot has a fixed `aspect-ratio` and `object-fit:
cover`, and every one prints its recommended size next to the field. The one place the rule
was missed three times is body copy: an alt text, three lede sentences and, before it, the
Free Videos closing band. That is now the pattern to check first on any new page.

---

## SUPERSEDED 2026-08-28 — the membership tiers are gone

Crystal removed the tier model on WhatsApp: 「我決定簡單些，只提供觀看課程，不設批改及Q & A」.
Paid now unlocks a course's video lessons and downloadable materials; unpaid sees public pages.
No Wanderer / Apprentice / Fellow, no Discord, no Q&A, no assignment marking.

**These rows in the register below describe fields that are being removed from the site and
should not be treated as live:**

| Row area | Field |
|---|---|
| Home — Community band | The "Fellow tier" eyebrow, heading, "See the tiers" button and its body paragraph |
| Courses overview | `THREE WAYS TO STUDY` tier description text (`cas_tier_*_text`) |
| Courses overview | Tier card background photos (`cas_tier_*_photo`) and the mandated darker-photo hint |
| Courses overview | Tier names, price lines, the `most picked` marker, and all three tier buttons |
| Showcase | The "Join as a Fellow" CTA band button |
| About | The `Discord` entry in the contact channel list |

The two register entries that were `OK` because a chapter opened them — the tier description
text and card photo, opened by DD:187 — are now moot: the chapter that opened them describes a
feature the client has removed. Do not re-open them elsewhere.

The counts at the top of this file have NOT been recalculated for this change; they describe the
2026-08-25 audit. Treat the table above as the correction.
