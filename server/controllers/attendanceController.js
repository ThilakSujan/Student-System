const Attendance = require('../models/Attendance');
const Student = require('../models/Student');
const AppError = require('../utils/AppError');
const emailService = require('../services/emailService');

exports.getAttendance = async (req, res, next) => {
  try {
    const role = req.userRole;
    let query = {};
    if (role === 'student') query.student = req.user.id;
    if (req.query.date) {
      const d = new Date(req.query.date);
      query.date = { $gte: new Date(d.setHours(0,0,0,0)), $lt: new Date(d.setHours(23,59,59,999)) };
    }
    const records = await Attendance.find(query).populate('student', 'student_name');
    res.status(200).json({ status: 'success', data: records });
  } catch (error) { next(error); }
};

exports.markAttendance = async (req, res, next) => {
  try {
    const { date, records } = req.body; // records = [{studentId, status}]
    const d = new Date(date);
    d.setHours(12, 0, 0, 0); // normalize time

    const updates = records.map(record => ({
      updateOne: {
        filter: { student: record.studentId, date: d },
        update: { $set: { status: record.status, marked_by: req.user.id } },
        upsert: true
      }
    }));

    await Attendance.bulkWrite(updates);

    // Send emails for absent students
    const absentees = records.filter(r => r.status === 'Absent');
    for (const absentee of absentees) {
      const student = await Student.findById(absentee.studentId);
      if (student && student.email) {
        emailService.sendAttendanceAlert(student.email, student.student_name, date, 'Absent').catch(console.error);
      }
    }

    res.status(200).json({ status: 'success', message: 'Attendance marked successfully' });
  } catch (error) { next(error); }
};
