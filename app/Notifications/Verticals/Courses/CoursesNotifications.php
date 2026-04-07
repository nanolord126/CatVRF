<?php declare(strict_types=1);

namespace App\Notifications\Verticals\Courses;

use Illuminate\Notifications\Notification;

final class EnrollmentConfirmedNotification extends Model
{

    private string $type = 'courses.enrollment.confirmed';
        private string $template = 'emails.courses.enrollment_confirmed';

        public function __construct(int $userId, int $tenantId, array $data = [])
        {
            parent::__construct($userId, $tenantId, $data, channels: ['mail', 'push', 'database']);
            $this->subject = 'Welcome to ' . ($data['course_name'] ?? 'Course');
        }
    }

    final class CourseStartedNotification extends BasePushNotification
    {
        private string $type = 'courses.course.started';

        public function __construct(int $userId, int $tenantId, array $data = [])
        {
            parent::__construct($userId, $tenantId, $data, channels: ['push', 'database']);

            $this->title('Course is now available!')
                 ->body('Start learning ' . ($data['course_name'] ?? ''))
                 ->type('action')
                 ->autoClose(0)
                 ->deepLink('/courses/' . ($data['course_id'] ?? '') . '/start');
        }
    }

    final class AssignmentGradedNotification extends BasePushNotification
    {
        private string $type = 'courses.assignment.graded';

        public function __construct(int $userId, int $tenantId, array $data = [])
        {
            parent::__construct($userId, $tenantId, $data, channels: ['push', 'database']);

            $this->title('Assignment graded')
                 ->body('Your score: ' . ($data['score'] ?? '0') . '%')
                 ->type('info')
                 ->autoClose(8000)
                 ->deepLink('/courses/' . ($data['course_id'] ?? '') . '/grades');
        }
    }

    final class CertificateIssuedNotification extends BaseMailableNotification
    {
        private string $type = 'courses.certificate.issued';
        private string $template = 'emails.courses.certificate_issued';

        public function __construct(int $userId, int $tenantId, array $data = [])
        {
            parent::__construct($userId, $tenantId, $data, channels: ['mail', 'push', 'database']);
            $this->subject = 'Congratulations! Your certificate is ready';
        }
}
