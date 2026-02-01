import FingerprintJS from '@fingerprintjs/fingerprintjs'

export async function getDeviceFingerprint(): Promise<string | null> {
  try {
    // Initialize the agent
    const fp = await FingerprintJS.load()

    // Get the visitor identifier
    const result = await fp.get()

    return result.visitorId
  } catch (error) {
    console.error('Failed to generate device fingerprint:', error)
    return null
  }
}

export async function generateFingerprint(): Promise<string | null> {
  return getDeviceFingerprint()
}