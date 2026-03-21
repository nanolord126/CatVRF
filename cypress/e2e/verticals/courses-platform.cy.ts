declare(strict_types=1);

describe('Courses & Learning Platform (Courses Vertical)', () => {
  beforeEach(() => {
    cy.login({ email: 'business@courses.test', password: 'password' });
    cy.visit('/app/courses');
  });

  describe('Course Catalog Management', () => {
    it('Should create a new course with title and description', () => {
      cy.get('button:contains("Create Course")').click();
      cy.get('input[name="title"]').type('Advanced TypeScript Mastery');
      cy.get('textarea[name="description"]').type('Deep dive into TypeScript advanced patterns');
      cy.get('input[name="price"]').type('4999');
      cy.get('select[name="category"]').select('programming');
      cy.get('button:contains("Save")').click();
      cy.contains('Course created successfully').should('be.visible');
    });

    it('Should display course in catalog', () => {
      cy.visit('/marketplace/courses');
      cy.get('[data-test="course-card"]').first().within(() => {
        cy.contains('Advanced TypeScript').should('be.visible');
        cy.get('[data-test="course-price"]').contains('4999').should('be.visible');
      });
    });

    it('Should support multiple course categories', () => {
      cy.get('[data-test="category-filter"]').click();
      cy.get('label:contains("Programming")').click();
      cy.get('button:contains("Apply")').click();
      cy.get('[data-test="course-card"]').should('have.length.greaterThan', 0);
    });

    it('Should update course content with idempotency', () => {
      cy.get('[data-test="course-row"]').first().click();
      cy.get('button:contains("Edit")').click();
      cy.get('textarea[name="description"]').clear().type('Updated course description');
      cy.get('button:contains("Save")').click();
      cy.get('button:contains("Save")').click(); // Duplicate click
      cy.contains('saved successfully').should('be.visible');
      cy.get('[data-test="course-description"]').contains('Updated course description').should('be.visible');
    });
  });

  describe('Lesson & Module Structure', () => {
    it('Should create lesson modules with sequential ordering', () => {
      cy.get('[data-test="course-row"]').first().click();
      cy.get('button:contains("Add Module")').click();
      cy.get('input[name="module_title"]').type('Module 1: Fundamentals');
      cy.get('textarea[name="module_description"]').type('Core concepts and basics');
      cy.get('input[name="module_order"]').type('1');
      cy.get('button:contains("Save Module")').click();
      cy.contains('Module created').should('be.visible');
    });

    it('Should add lessons to modules', () => {
      cy.get('[data-test="module-card"]').first().within(() => {
        cy.get('button:contains("Add Lesson")').click();
      });
      cy.get('input[name="lesson_title"]').type('Lesson 1: Getting Started');
      cy.get('textarea[name="lesson_content"]').type('Introduction to the course framework');
      cy.get('input[name="duration_minutes"]').type('45');
      cy.get('input[name="order"]').type('1');
      cy.get('button:contains("Save Lesson")').click();
      cy.contains('Lesson added').should('be.visible');
    });

    it('Should support video/media attachments in lessons', () => {
      cy.get('[data-test="lesson-card"]').first().within(() => {
        cy.get('button:contains("Edit")').click();
      });
      cy.get('input[type="file"]').selectFile('cypress/fixtures/sample-video.mp4');
      cy.get('select[name="media_type"]').select('video');
      cy.get('button:contains("Save")').click();
      cy.contains('Media attached').should('be.visible');
    });

    it('Should enforce lesson ordering', () => {
      cy.get('[data-test="module-card"]').first().within(() => {
        cy.get('[data-test="lesson-item"]').should('have.length', 3);
        cy.get('[data-test="lesson-item"]').first().contains('Lesson 1');
        cy.get('[data-test="lesson-item"]').eq(1).contains('Lesson 2');
      });
    });
  });

  describe('Student Enrollments & Progress Tracking', () => {
    it('Should enroll student in course with payment hold', () => {
      cy.visit('/marketplace/courses');
      cy.get('[data-test="course-card"]').first().within(() => {
        cy.get('button:contains("Enroll")').click();
      });
      cy.contains('Enroll in Course').should('be.visible');
      cy.get('button:contains("Confirm Purchase")').click();
      cy.contains('Payment processing').should('be.visible');
      cy.wait(1000);
      cy.contains('Successfully enrolled').should('be.visible');
    });

    it('Should track student learning progress', () => {
      cy.get('[data-test="student-dashboard"]').click();
      cy.get('[data-test="course-progress"]').should('be.visible');
      cy.get('[data-test="progress-bar"]').should('have.attr', 'style').and('include', 'width: 0%');
    });

    it('Should mark lessons as completed', () => {
      cy.get('[data-test="lesson-card"]').first().click();
      cy.get('button:contains("Mark as Complete")').click();
      cy.contains('Lesson marked complete').should('be.visible');
      cy.get('[data-test="progress-bar"]').should('have.attr', 'style').and('include', 'width: ');
    });

    it('Should calculate completion percentage correctly', () => {
      cy.get('[data-test="progress-indicator"]').contains('Progress:').within(() => {
        cy.contains(/\d+%/).should('be.visible');
      });
    });

    it('Should issue certificate on course completion', () => {
      cy.get('[data-test="student-dashboard"]').click();
      cy.get('[data-test="course-card"]').first().within(() => {
        cy.get('[data-test="progress-bar"]').should('have.attr', 'style').and('include', '100%');
        cy.get('button:contains("Get Certificate")').should('be.enabled');
      });
      cy.get('button:contains("Get Certificate")').click();
      cy.contains('Certificate generated').should('be.visible');
    });
  });

  describe('Instructor Tools & Analytics', () => {
    it('Should display student roster and enrollment status', () => {
      cy.get('[data-test="course-row"]').first().click();
      cy.get('button:contains("Students")').click();
      cy.get('[data-test="student-table"]').should('be.visible');
      cy.get('[data-test="student-row"]').should('have.length.greaterThan', 0);
    });

    it('Should track student completion rates', () => {
      cy.get('[data-test="course-analytics"]').click();
      cy.get('[data-test="completion-rate"]').contains(/\d+%/).should('be.visible');
      cy.get('[data-test="completion-chart"]').should('be.visible');
    });

    it('Should show student engagement metrics', () => {
      cy.get('[data-test="course-analytics"]').click();
      cy.get('[data-test="avg-lesson-time"]').should('contain', 'minutes');
      cy.get('[data-test="total-views"]').should('contain.text', /\d+/);
    });

    it('Should provide revenue metrics for instructor', () => {
      cy.get('[data-test="instructor-earnings"]').click();
      cy.get('[data-test="total-revenue"]').should('contain', '₽');
      cy.get('[data-test="avg-revenue-per-student"]').should('contain', '₽');
    });

    it('Should support course announcements to all students', () => {
      cy.get('[data-test="course-row"]').first().click();
      cy.get('button:contains("Send Announcement")').click();
      cy.get('textarea[name="announcement"]').type('Course update: New lesson added');
      cy.get('button:contains("Send")').click();
      cy.contains('Announcement sent to all students').should('be.visible');
    });
  });

  describe('Review & Rating System', () => {
    it('Should submit course review after completion', () => {
      cy.get('[data-test="course-card"]').first().within(() => {
        cy.contains('Leave a Review').click();
      });
      cy.get('input[name="rating"]').eq(0).parent().click(); // 5 stars
      cy.get('textarea[name="review"]').type('Excellent course, highly recommended');
      cy.get('button:contains("Submit Review")').click();
      cy.contains('Review submitted').should('be.visible');
    });

    it('Should display course rating with review count', () => {
      cy.visit('/marketplace/courses');
      cy.get('[data-test="course-card"]').first().within(() => {
        cy.get('[data-test="course-rating"]').should('contain', '★');
        cy.get('[data-test="review-count"]').should('contain', 'reviews');
      });
    });

    it('Should prevent review spam with idempotency', () => {
      cy.get('button:contains("Leave a Review")').click();
      cy.get('textarea[name="review"]').type('Great course');
      cy.get('button:contains("Submit Review")').click();
      cy.get('button:contains("Submit Review")').click(); // Double click
      cy.contains('already reviewed').should('be.visible');
    });
  });

  describe('Refund & Access Control', () => {
    it('Should allow refund within refund period', () => {
      cy.get('[data-test="student-courses"]').click();
      cy.get('[data-test="course-card"]').first().within(() => {
        cy.get('button:contains("Request Refund")').click();
      });
      cy.get('textarea[name="refund_reason"]').type('Not suitable for my level');
      cy.get('button:contains("Submit Request")').click();
      cy.contains('Refund request submitted').should('be.visible');
    });

    it('Should revoke access after refund', () => {
      cy.get('[data-test="student-courses"]').click();
      cy.get('[data-test="course-card"]').first().within(() => {
        cy.get('button:contains("Request Refund")').click();
      });
      cy.contains('Confirm Refund').within(() => {
        cy.get('button:contains("Yes")').click();
      });
      cy.wait(2000);
      cy.get('[data-test="student-courses"]').click();
      cy.get('[data-test="course-card"]').contains('Previous Course').should('not.exist');
    });

    it('Should prevent access to course content after refund', () => {
      cy.visit('/app/courses/123/lessons');
      cy.contains('Access denied: Course refunded').should('be.visible');
    });
  });

  describe('Course Scheduling & Deadlines', () => {
    it('Should set course availability dates', () => {
      cy.get('[data-test="course-row"]').first().click();
      cy.get('button:contains("Edit Course")').click();
      cy.get('input[name="available_from"]').type('2026-04-01');
      cy.get('input[name="available_until"]').type('2026-12-31');
      cy.get('button:contains("Save")').click();
      cy.contains('Dates updated').should('be.visible');
    });

    it('Should enforce course availability', () => {
      cy.visit('/marketplace/courses');
      cy.get('[data-test="course-card"]')
        .filter(':contains("Future Course")')
        .within(() => {
          cy.get('button:contains("Enroll")').should('be.disabled');
          cy.contains('Available from').should('be.visible');
        });
    });

    it('Should set lesson deadlines for assignments', () => {
      cy.get('[data-test="lesson-card"]').first().click();
      cy.get('button:contains("Edit Lesson")').click();
      cy.get('input[name="assignment_due"]').type('2026-03-25 23:59');
      cy.get('button:contains("Save")').click();
      cy.contains('Deadline set').should('be.visible');
    });
  });

  describe('Payment & Payout System', () => {
    it('Should validate payment hold when course payment initiated', () => {
      cy.visit('/marketplace/courses');
      cy.get('[data-test="course-card"]').first().click();
      cy.get('button:contains("Enroll")').click();
      cy.wait(500);
      cy.contains('Checking fraud score').should('be.visible');
      cy.wait(500);
      cy.contains('Payment authorized').should('be.visible');
    });

    it('Should calculate commission correctly on course sale', () => {
      cy.get('[data-test="instructor-earnings"]').click();
      cy.get('[data-test="transaction-row"]').first().within(() => {
        cy.get('[data-test="amount"]').contains('₽').then(($el) => {
          const amount = parseInt($el.text());
          expect(amount).to.be.greaterThan(0);
        });
        cy.get('[data-test="commission"]').contains('₽').then(($el) => {
          const commission = parseInt($el.text());
          expect(commission).to.be.lessThan(amount);
        });
      });
    });

    it('Should log all payment transactions for audit', () => {
      cy.visit('/app/courses');
      cy.get('[data-test="transactions"]').click();
      cy.get('[data-test="transaction-log"]').should('be.visible');
      cy.get('[data-test="transaction-row"]').first().within(() => {
        cy.get('[data-test="correlation-id"]').should('not.be.empty');
        cy.get('[data-test="timestamp"]').should('be.visible');
      });
    });
  });

  describe('Cross-Vertical Integration', () => {
    it('Should integrate with wishlist system', () => {
      cy.visit('/marketplace/courses');
      cy.get('[data-test="course-card"]').first().within(() => {
        cy.get('button:contains("♡")').click();
      });
      cy.contains('Added to wishlist').should('be.visible');
      cy.get('[data-test="user-menu"]').click();
      cy.get('[data-test="wishlist"]').click();
      cy.contains('Advanced TypeScript').should('be.visible');
    });

    it('Should support course recommendations based on behavior', () => {
      cy.visit('/marketplace/courses');
      cy.get('[data-test="recommended-section"]').should('be.visible');
      cy.get('[data-test="recommendation-card"]').should('have.length.greaterThan', 0);
    });
  });
});
