import api from './axios';

export const getNotifications = () => api.get('/notifications');
export const getMyNotifications = () => api.get('/notifications/my');
export const createNotification = (data) => api.post('/notifications', data);
export const deleteNotification = (id) => api.delete(`/notifications/${id}`);
export const updateNotification = (id, data) => api.put(`/notifications/${id}`, data);
