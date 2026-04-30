import { Metadata } from 'next'
import NotificationDistributionTracker from '@/components/business/notifications/NotificationDistributionTracker'

export const metadata: Metadata = {
  title: 'Notification Distributions | CatVRF',
  description: 'Track and manage notification distributions in real-time',
}

export default function NotificationDistributionsPage() {
  return (
    <div className="min-h-screen bg-gray-50">
      <NotificationDistributionTracker />
    </div>
  )
}
