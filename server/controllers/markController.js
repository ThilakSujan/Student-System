const Mark = require('../models/Mark');
const Student = require('../models/Student');
const AppError = require('../utils/AppError');
const emailService = require('../services/emailService');

exports.getAllMarks = async (req, res, next) => {
  try {
    const role = req.userRole;
    let query = {};
    if (role === 'student') {
      query.student = req.user.id;
      query.published = true; // Students only see published marks
    }
    const marks = await Mark.find(query).populate('student', 'student_name').populate('subject', 'subject_name subject_code');
    res.status(200).json({ status: 'success', data: marks });
  } catch (error) { next(error); }
};

exports.addMarks = async (req, res, next) => {
  try {
    const { marks } = req.body; // Array of marks
    const createdMarks = await Mark.insertMany(marks);
    res.status(201).json({ status: 'success', data: createdMarks });
  } catch (error) { next(error); }
};

exports.updateMark = async (req, res, next) => {
  try {
    const updatedMark = await Mark.findByIdAndUpdate(req.params.id, req.body, { new: true });
    if (!updatedMark) throw new AppError('Mark not found', 404);
    res.status(200).json({ status: 'success', data: updatedMark });
  } catch (error) { next(error); }
};

exports.publishMarks = async (req, res, next) => {
  try {
    const { markIds } = req.body; // Array of mark ids to publish
    
    // Update marks to published
    await Mark.updateMany(
      { _id: { $in: markIds } },
      { $set: { published: true, published_at: Date.now(), published_by: req.user.id } }
    );

    // Get the published marks to send emails
    const publishedMarks = await Mark.find({ _id: { $in: markIds } })
      .populate('student', 'email student_name')
      .populate('subject', 'subject_name');

    // Notify students
    for (const mark of publishedMarks) {
      if (mark.student && mark.student.email) {
        emailService.sendMarksPublished(
          mark.student.email, 
          mark.student.student_name, 
          `${mark.subject.subject_name}: ${mark.marks_obtained}/${mark.total_marks}`
        ).catch(console.error);
      }
    }

    res.status(200).json({ status: 'success', message: 'Marks published successfully' });
  } catch (error) { next(error); }
};
