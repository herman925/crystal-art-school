/* Tutor's native image fields participate in its normal Update and media library flows. */
( function () {
	'use strict';
	var attempts = 0;
	function register() {
		if ( window.Tutor && window.Tutor.CourseBuilder ) {
			window.Tutor.CourseBuilder.Basic.registerField( 'after_description', window.casCoursePhotoFields );
			window.Tutor.CourseBuilder.Curriculum.Lesson.registerField( 'after_description', window.casCourseLessonFields );
			return;
		}
		// Tutor exposes its extension API after React mounts, after the script load event.
		if ( ++attempts < 100 ) { window.setTimeout( register, 100 ); }
	}
	register();
} )();
