const mongoose = require('mongoose');

const marksSchema = new mongoose.Schema(
  {
    student_id: {
      type: mongoose.Schema.Types.ObjectId,
      ref: 'Student',
      required: [true, 'Student is required'],
    },
    subject_id: {
      type: mongoose.Schema.Types.ObjectId,
      ref: 'Subject',
      required: [true, 'Subject is required'],
    },
    class_id: {
      type: mongoose.Schema.Types.ObjectId,
      ref: 'Class',
      default: null,
    },
    exam_id: {
      type: mongoose.Schema.Types.ObjectId,
      ref: 'Exam',
      default: null,
    },
    exam_type: {
      type: String,
      enum: ['Unit Test', 'Mid Term', 'Final', 'Assignment', 'Quiz', 'Practical', 'Other'],
      default: 'Final',
    },
    marks_obtained: {
      type: Number,
      required: [true, 'Marks obtained is required'],
      min: [0, 'Marks cannot be negative'],
    },
    total_marks: {
      type: Number,
      required: [true, 'Total marks is required'],
      min: [1, 'Total marks must be at least 1'],
    },
    grade: {
      type: String,
      trim: true,
      default: null,
    },
    percentage: {
      type: Number,
      default: null,
    },
    remarks: {
      type: String,
      trim: true,
      default: null,
    },
    exam_date: {
      type: Date,
      default: null,
    },
    is_published: {
      type: Boolean,
      default: false,
    },
    is_notified: {
      type: Boolean,
      default: false,
    },
    published_at: {
      type: Date,
      default: null,
    },
    notified_at: {
      type: Date,
      default: null,
    },
    entered_by: {
      type: mongoose.Schema.Types.ObjectId,
      ref: 'User',
      default: null,
    },
  },
  {
    timestamps: { createdAt: 'created_at', updatedAt: 'updated_at' },
  }
);

// Unique constraint: one mark entry per student per subject per exam type
marksSchema.index(
  { student_id: 1, subject_id: 1, exam_type: 1 },
  { unique: true }
);

// Pre-save: auto-calculate grade and percentage
marksSchema.pre('save', function (next) {
  if (this.isModified('marks_obtained') || this.isModified('total_marks')) {
    if (this.total_marks > 0) {
      this.percentage = parseFloat(((this.marks_obtained / this.total_marks) * 100).toFixed(2));
      const pct = this.percentage;
      if (pct >= 90) this.grade = 'A+';
      else if (pct >= 80) this.grade = 'A';
      else if (pct >= 70) this.grade = 'B';
      else if (pct >= 60) this.grade = 'C';
      else if (pct >= 50) this.grade = 'D';
      else this.grade = 'F';
    }
  }
  next();
});

const Marks = mongoose.model('Marks', marksSchema);
module.exports = Marks;
